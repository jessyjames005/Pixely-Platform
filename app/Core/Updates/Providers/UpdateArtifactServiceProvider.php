<?php

declare(strict_types=1);

namespace App\Core\Updates\Providers;

use App\Core\Updates\Contracts\ArtifactHostPolicyInterface;
use App\Core\Updates\Contracts\ArtifactSourceInterface;
use App\Core\Updates\Contracts\UpdateVerifierInterface;
use App\Core\Updates\Services\ConfiguredArtifactHostPolicy;
use App\Core\Updates\Services\HttpArtifactSource;
use App\Core\Updates\Services\SodiumUpdateVerifier;
use Illuminate\Support\ServiceProvider;

/** Registers U4's artifact download and verification services. */
final class UpdateArtifactServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ArtifactHostPolicyInterface::class, static fn ($app) => new ConfiguredArtifactHostPolicy(
            (array) $app['config']->get('update-manager.artifact_hosts', []),
        ));
        $this->app->singleton(ArtifactSourceInterface::class, static fn ($app) => new HttpArtifactSource(
            $app->make(ArtifactHostPolicyInterface::class),
            (int) $app['config']->get('update-manager.max_artifact_bytes', 104857600),
            (int) $app['config']->get('update-manager.download_timeout_seconds', 60),
        ));
        $this->app->singleton(UpdateVerifierInterface::class, static fn ($app) => new SodiumUpdateVerifier(
            (array) $app['config']->get('update-manager.signing_public_keys', []),
        ));
    }
}
