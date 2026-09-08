<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Scaffolds a new, empty, valid extension: manifest, main class,
 * provider, routes, an empty controller, migrations/lang/upgrade
 * directories, and a minimal frontend skeleton following the
 * platform's per-domain structure.
 *
 * The generated extension is automatically discovered by the Kernel
 * on the next boot — no manual registration step required for the
 * backend. Frontend wiring (Vite/TS aliases, navigation registry)
 * still requires a few manual edits, printed at the end.
 */
final class MakeExtension extends Command
{
    protected $signature = 'make:extension {name : StudlyCase extension name, e.g. Blog}';

    protected $description = 'Scaffold a new, empty extension (backend + frontend skeleton)';

    public function handle(): int
    {
        $studly = Str::studly($this->argument('name'));
        $id = Str::kebab($studly);
        $basePath = base_path("app/Extensions/{$studly}");

        if (is_dir($basePath)) {
            $this->error("Extension [{$studly}] already exists at {$basePath}.");
            return self::FAILURE;
        }

        $this->scaffoldBackend($studly, $id, $basePath);
        $this->scaffoldFrontend($studly, $id, $basePath);

        $this->info("Extension [{$studly}] scaffolded at app/Extensions/{$studly}.");
        $this->printNextSteps($studly, $id);

        return self::SUCCESS;
    }

    private function scaffoldBackend(string $studly, string $id, string $basePath): void
    {
        $dirs = [
            "{$basePath}/Providers",
            "{$basePath}/Http/Controllers/Api",
            "{$basePath}/Models",
            "{$basePath}/Database/Migrations",
            "{$basePath}/Upgrades",
            "{$basePath}/routes",
            "{$basePath}/lang/en",
            "{$basePath}/lang/fr",
            "{$basePath}/tests",
        ];

        foreach ($dirs as $dir) {
            File::ensureDirectoryExists($dir);
        }

        File::put("{$basePath}/extension.php", $this->manifestStub($studly, $id));
        File::put("{$basePath}/{$studly}Extension.php", $this->extensionClassStub($studly, $id));
        File::put("{$basePath}/Providers/{$studly}ServiceProvider.php", $this->providerStub($studly));
        File::put("{$basePath}/Http/Controllers/Api/{$studly}Controller.php", $this->controllerStub($studly, $id));
        File::put("{$basePath}/routes/api.php", $this->routesStub($studly, $id));
        File::put("{$basePath}/lang/en/{$id}.php", $this->langStub());
        File::put("{$basePath}/lang/fr/{$id}.php", $this->langStub());

        // Keep otherwise-empty directories tracked by git
        foreach (["{$basePath}/Models", "{$basePath}/Database/Migrations", "{$basePath}/Upgrades", "{$basePath}/tests"] as $emptyDir) {
            if (File::allFiles($emptyDir) === []) {
                File::put("{$emptyDir}/.gitkeep", '');
            }
        }
    }

    private function scaffoldFrontend(string $studly, string $id, string $basePath): void
    {
        $jsBase = "{$basePath}/resources/js";

        foreach (['store', 'models', 'views', 'components', 'composables', 'tests'] as $dir) {
            File::ensureDirectoryExists("{$jsBase}/{$dir}");
            if (File::allFiles("{$jsBase}/{$dir}") === []) {
                File::put("{$jsBase}/{$dir}/.gitkeep", '');
            }
        }

        File::put("{$jsBase}/models/" . $studly . '.ts', $this->frontendModelStub($studly));
        File::put("{$jsBase}/store/" . Str::camel($id) . '.store.ts', $this->frontendStoreStub($studly, $id));
        File::put("{$jsBase}/views/{$studly}View.vue", $this->frontendViewStub($studly, $id));
        File::put("{$basePath}/resources/js/nav.ts", $this->frontendNavStub($studly, $id));
    }

    private function manifestStub(string $studly, string $id): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        /**
         * {$studly} extension manifest.
         */
        return [
            'id' => '{$id}',
            'name' => '{$studly}',
            'version' => '1.0.0',
            'class' => App\\Extensions\\{$studly}\\{$studly}Extension::class,
        ];

        PHP;
    }

    private function extensionClassStub(string $studly, string $id): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\Extensions\\{$studly};

        use App\\Core\\Extensions\\Contracts\\ExtensionInterface;
        use App\\Core\\Extensions\\Manifest\\ExtensionManifest;
        use App\\Core\\Extensions\\Permissions\\ExtensionPermissionsInterface;
        use App\\Extensions\\{$studly}\\Providers\\{$studly}ServiceProvider;

        /**
         * {$studly} extension.
         *
         * Add ExtensionUpgradableInterface (see App\\Core\\Extensions\\Versioning)
         * once this extension ships its first versioned upgrade step, and
         * ExtensionTranslatableInterface (see App\\Core\\Extensions\\Translations)
         * once its lang/ files are ready to be browsable in the Translations
         * extension screen.
         */
        final class {$studly}Extension implements ExtensionInterface, ExtensionPermissionsInterface
        {
            public function manifest(): ExtensionManifest
            {
                return new ExtensionManifest(
                    id: '{$id}',
                    name: '{$studly}',
                    version: '1.0.0',
                    class: self::class,
                    path: 'app/Extensions/{$studly}',
                    dependencies: [],
                );
            }

            /**
             * Declared permissions, following the platform convention:
             * <domain>.<object>.<view|manage|delete>. Synced automatically
             * on install/update/enable — see ExtensionPermissionSynchronizer.
             *
             * @return array<int, string>
             */
            public function declaredPermissions(): array
            {
                return [
                    '{$id}.items.view',
                    '{$id}.items.manage',
                    '{$id}.items.delete',
                ];
            }

            /**
             * @return array<class-string>
             */
            public function providers(): array
            {
                return [
                    {$studly}ServiceProvider::class,
                ];
            }

            public function boot(): void
            {
                // Nothing to boot.
            }
        }

        PHP;
    }

    private function providerStub(string $studly): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\Extensions\\{$studly}\\Providers;

        use Illuminate\\Support\\ServiceProvider;

        /**
         * Registers {$studly} extension API routes and migrations.
         */
        final class {$studly}ServiceProvider extends ServiceProvider
        {
            public function boot(): void
            {
                \$this->app->router
                    ->middleware('api')
                    ->prefix('api/v1')
                    ->group(
                        __DIR__ . '/../routes/api.php'
                    );

                \$this->loadMigrationsFrom(
                    __DIR__ . '/../Database/Migrations'
                );
            }
        }

        PHP;
    }

    private function controllerStub(string $studly, string $id): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\\Extensions\\{$studly}\\Http\\Controllers\\Api;

        use App\\Core\\Api\\Response\\ApiCollectionResponse;
        use Dedoc\\Scramble\\Attributes\\Group;
        use Illuminate\\Http\\JsonResponse;

        /**
         * Handles {$studly} API requests.
         *
         * This is an empty starting point — add methods (index, store,
         * show, update, destroy) following the same pattern as
         * App\\Extensions\\Gallery\\Http\\Controllers\\Api\\GalleryController.
         */
        #[Group('{$studly}', weight: 10)]
        final class {$studly}Controller
        {
            public function index(ApiCollectionResponse \$apiResponse): JsonResponse
            {
                return \$apiResponse->response(data: [], meta: ['total' => 0]);
            }
        }

        PHP;
    }

    private function routesStub(string $studly, string $id): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        use App\\Extensions\\{$studly}\\Http\\Controllers\\Api\\{$studly}Controller;
        use Illuminate\\Support\\Facades\\Route;

        /**
         * {$studly} extension API routes.
         *
         * Registered under api/v1 by {$studly}ServiceProvider, following
         * the same per-extension routing convention as other extensions.
         */
        Route::middleware(['auth:sanctum', 'permission:{$id}.items.view'])->group(function () {
            Route::get('/{$id}', [{$studly}Controller::class, 'index']);
        });

        PHP;
    }

    private function langStub(): string
    {
        return <<<'PHP'
        <?php

        declare(strict_types=1);

        return [
            // Follow the platform naming convention:
            // object.<object>.<property>[.hint], action.<verb>,
            // title.<context>, msg.<context>, tab.<name>,
            // preference.<name>, permission.<name>
        ];

        PHP;
    }

    private function frontendModelStub(string $studly): string
    {
        return <<<TS
        // {$studly} resource shape as returned by the API
        export interface {$studly}Item {
          id: number
        }

        TS;
    }

    private function frontendStoreStub(string $studly, string $id): string
    {
        $camel = Str::camel($id);

        return <<<TS
        // Pinia store for the {$studly} extension.
        import { defineStore } from 'pinia'
        import { apiClient } from '@shared/services/apiClient'
        import type { ApiCollectionResponse } from '@shared/types/api'
        import type { {$studly}Item } from '../models/{$studly}'

        interface {$studly}State {
          items: {$studly}Item[]
        }

        export const use{$studly}Store = defineStore('{$camel}', {
          state: (): {$studly}State => ({
            items: [],
          }),

          actions: {
            async fetchItems(): Promise<void> {
              const result = await apiClient.get<ApiCollectionResponse<{$studly}Item>>('/{$id}')
              this.items = result.data
            },
          },
        })

        TS;
    }

    private function frontendViewStub(string $studly, string $id): string
    {
        return <<<VUE
        <script setup lang="ts">
        // {$studly} administration screen — starting skeleton.
        import { onMounted } from 'vue'
        import { useApi } from '@shared/composables/useApi'
        import { use{$studly}Store } from '../store/{$id}.store'

        const store = use{$studly}Store()
        const { loading, error, execute: fetchItems } = useApi(store.fetchItems)

        onMounted(() => {
          fetchItems()
        })
        </script>

        <template>
          <div>
            <h1 class="text-h5 mb-4">{$studly}</h1>

            <v-card>
              <v-card-text>
                <v-alert v-if="error" type="error" density="compact">{{ error.message }}</v-alert>
                <p v-if="loading">Loading…</p>
                <p v-else-if="store.items.length === 0" class="text-medium-emphasis">No items yet.</p>
              </v-card-text>
            </v-card>
          </div>
        </template>

        VUE;
    }

    private function frontendNavStub(string $studly, string $id): string
    {
        return <<<TS
        import type { NavItem } from '@shared/navigation/types'

        export const {Str::camel($id)}NavItem: NavItem = {
          label: '{$studly}',
          to: '/admin/{$id}',
          icon: 'mdi-puzzle-outline',
          permission: '{$id}.items.view',
          extensionId: '{$id}',
        }

        TS;
    }

    private function printNextSteps(string $studly, string $id): void
    {
        $camel = Str::camel($id);

        $this->newLine();
        $this->line('<comment>Remaining manual steps:</comment>');
        $this->line('1. Add a Vite/TS alias in vite.config.js and tsconfig.json:');
        $this->line("   \"@extensions/{$id}\": .../app/Extensions/{$studly}/resources/js");
        $this->line('2. Import and register the nav item in resources/js/shared/navigation/registry.ts:');
        $this->line("   import { {$camel}NavItem } from '@extensions/{$id}/nav'");
        $this->line("3. Add the route in resources/js/router/index.ts (import {$studly}View, add to children[]).");
        $this->line("4. Add {$id}.items.view/manage/delete to database/seeders/RolePermissionSeeder.php (or rely on ExtensionPermissionSynchronizer at enable time).");
        $this->line('5. Run: php artisan migrate  (once you add migrations to Database/Migrations/).');
        $this->line('6. Run: php artisan pixely:extensions  to confirm discovery.');
    }
}
