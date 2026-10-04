<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\Extensions\Configuration\DatabaseExtensionConfigurationRepository;
use App\Core\Extensions\Configuration\ExtensionConfigurationRepositoryInterface;
use App\Core\Translations\Contracts\TranslationFileSystemInterface;
use App\Core\Translations\Services\LocalTranslationFileSystem;
use App\Media\Contracts\ImageProcessorInterface;
use App\Media\Contracts\StorageInterface;
use App\Media\Drivers\LocalStorage;
use App\Media\Processors\InterventionImageProcessor;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\RequestBodyObject;
use Dedoc\Scramble\Support\Generator\Response as ScrambleResponse;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Scramble::ignoreDefaultRoutes();

        $this->app->bind(
            StorageInterface::class,
            LocalStorage::class
        );

        $this->app->bind(
            ImageProcessorInterface::class,
            InterventionImageProcessor::class
        );

        $this->app->bind(
            TranslationFileSystemInterface::class,
            LocalTranslationFileSystem::class,
        );

        $this->app->singleton(
            ExtensionConfigurationRepositoryInterface::class,
            DatabaseExtensionConfigurationRepository::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Scramble::afterOpenApiGenerated(function (OpenApi $openApi): void {
            // The entire Pixely API is strict JSON:API: every response and
            // request body is exchanged as `application/vnd.api+json`
            // (enforced at runtime by EnsureJsonApiMediaType). Scramble
            // cannot infer this from laravel-json-api's DataResponse and
            // defaults to `application/json`, so normalise the generated
            // document here. Multipart upload bodies are left untouched.
            $normalise = function (array &$content): void {
                if (array_key_exists('application/json', $content)) {
                    $content['application/vnd.api+json'] = $content['application/json'];
                    unset($content['application/json']);
                }
            };

            foreach ($openApi->paths as $path) {
                foreach ($path->operations as $operation) {
                    foreach ($operation->responses ?? [] as $response) {
                        if ($response instanceof ScrambleResponse) {
                            $normalise($response->content);
                        }
                    }

                    if ($operation->requestBodyObject instanceof RequestBodyObject) {
                        $normalise($operation->requestBodyObject->content);
                    }
                }
            }

            foreach ($openApi->components->responses as $response) {
                $normalise($response->content);
            }

            // Scramble cannot read descriptions from laravel-json-api's
            // JsonApiController (its inherited CRUD methods only yield a
            // generic summary). Replace those generic summaries with
            // extension- and resource-aware text so every documented route
            // carries a useful description. Controllers that already
            // document their own methods (Tuleap, Core, ...) keep their
            // summaries untouched — only the generic templates match here.
            $resourceLabel = static function (string $reference): string {
                return match (true) {
                    str_contains($reference, 'photos') => 'gallery photo',
                    str_contains($reference, 'files') => 'file',
                    str_contains($reference, 'users') => 'user',
                    str_contains($reference, 'roles') => 'role',
                    str_contains($reference, 'permissions') => 'permission',
                    default => 'resource',
                };
            };

            $genericToSummary = [
                'Fetch zero to many JSON API resources' => fn (string $singular, string $plural): string => "List {$plural}",
                'Fetch zero to one JSON API resource by id' => fn (string $singular, string $plural): string => "Get a {$singular}",
                'Create a new resource' => fn (string $singular, string $plural): string => "Create a {$singular}",
                'Update an existing resource' => fn (string $singular, string $plural): string => "Update a {$singular}",
                'Destroy a resource' => fn (string $singular, string $plural): string => "Delete a {$singular}",
                'Fetch the resource identifier(s) for a JSON API relationship' => fn (string $singular, string $plural): string => "List {$plural} relationships",
                'Update a resource relationship' => fn (string $singular, string $plural): string => "Update {$plural} relationships",
            ];

            foreach ($openApi->paths as $pathItem) {
                foreach ($pathItem->operations as $operation) {
                    if (! isset($genericToSummary[$operation->summary])) {
                        continue;
                    }

                     $label = $resourceLabel($operation->operationId ?? $pathItem->path);
                    $singular = $label;
                    $plural = $label === 'gallery photo' ? 'gallery photos' : $label.'s';
                    $operation->summary = ($genericToSummary[$operation->summary])($singular, $plural);
                    $operation->description = $operation->summary;
                }
            }
        });
    }
}
