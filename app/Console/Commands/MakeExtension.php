<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Scaffolds a new, empty, valid extension: manifest, main class,
 * provider, API route entrypoint, migrations/lang/upgrade directories,
 * surface directories (Public/User/Admin/API), and a minimal frontend
 * skeleton following the platform per-domain structure.
 *
 * API routes and admin navigation are registered automatically by SDK v2.
 * Frontend aliases and route components remain explicit until the dynamic
 * frontend route registry is introduced in a later SDK v2 lot.
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
            "{$basePath}/Public",
            "{$basePath}/User",
            "{$basePath}/Admin",
            "{$basePath}/API",
            "{$basePath}/Providers",
            "{$basePath}/Http/Controllers/Api",
            "{$basePath}/Models",
            "{$basePath}/Database/Migrations",
            "{$basePath}/Upgrades",
            "{$basePath}/lang/en",
            "{$basePath}/lang/fr",
            "{$basePath}/tests",
            "{$basePath}/tests/Unit",
            "{$basePath}/tests/Functional",
            "{$basePath}/tests/E2E",
        ];

        foreach ($dirs as $dir) {
            File::ensureDirectoryExists($dir);
        }

        File::put("{$basePath}/extension.php", $this->manifestStub($studly, $id));
        File::put("{$basePath}/{$studly}Extension.php", $this->extensionClassStub($studly, $id));
        File::put("{$basePath}/Providers/{$studly}ServiceProvider.php", $this->providerStub($studly));
        File::put("{$basePath}/Http/Controllers/Api/{$studly}Controller.php", $this->controllerStub($studly, $id));
        File::put("{$basePath}/API/routes.php", $this->routesStub($studly, $id));
        File::put("{$basePath}/lang/en/{$id}.php", $this->langStub());
        File::put("{$basePath}/lang/fr/{$id}.php", $this->langStub());
        File::put("{$basePath}/tests/Unit/{$studly}ExtensionTest.php", $this->unitTestStub($studly, $id));
        File::put("{$basePath}/tests/Functional/{$studly}ApiTest.php", $this->functionalTestStub($studly, $id));
        File::put("{$basePath}/tests/E2E/{$studly}.spec.ts", $this->e2eTestStub($studly, $id));

        // Keep otherwise-empty directories tracked by git
        foreach (["{$basePath}/Public", "{$basePath}/User", "{$basePath}/Admin", "{$basePath}/Models", "{$basePath}/Database/Migrations", "{$basePath}/Upgrades", "{$basePath}/tests"] as $emptyDir) {
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
        File::put("{$jsBase}/store/{$id}.store.ts", $this->frontendStoreStub($studly, $id));
        File::put("{$jsBase}/views/{$studly}View.vue", $this->frontendViewStub($studly, $id));
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
            'minimum_kernel_version' => '1.0.0',
            'surfaces' => ['admin', 'api'],
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

        use App\\Core\\Extensions\\Capabilities\\Contracts\\ExtensionNavigationInterface;
        use App\\Core\\Extensions\\Capabilities\\Contracts\\ExtensionRoutesInterface;
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
        final class {$studly}Extension implements ExtensionInterface, ExtensionNavigationInterface, ExtensionRoutesInterface, ExtensionPermissionsInterface
        {
            public function manifest(): ExtensionManifest
            {
                return new ExtensionManifest(
                    id: '{$id}',
                    name: '{$studly}',
                    version: '1.0.0',
                    minimum_kernel_version: '1.0.0',
                    class: self::class,
                    path: 'app/Extensions/{$studly}',
                    dependencies: [],
                    surfaces: ['admin', 'api'],
                );
            }

            /**
             * @return array<int, array<string, mixed>>
             */
            public function navigation(): array
            {
                return [[
                    'id' => '{$id}',
                    'label' => '{$studly}',
                    'to' => '/admin/{$id}',
                    'icon' => 'mdi-puzzle-outline',
                    'permission' => '{$id}.items.view',
                    'surface' => 'admin',
                    'order' => 100,
                    'extensionId' => '{$id}',
                ]];
            }

            /**
             * @return list<array{file:string}>
             */
            public function routes(): array
            {
                return [['file' => 'app/Extensions/{$studly}/API/routes.php']];
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
         * Registers {$studly} extension services and migrations.
         * API routes are registered centrally by the Extension SDK v2.
         */
        final class {$studly}ServiceProvider extends ServiceProvider
        {
            public function boot(): void
            {
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

        use Dedoc\\Scramble\\Attributes\\Group;
        use LaravelJsonApi\\Core\\Responses\\DataResponse;

        /**
         * Handles {$studly} API requests.
         *
         * Returns strict JSON:API documents via LaravelJsonApi. Add methods
         * (index, store, show, update, destroy) following the same pattern as
         * App\\Extensions\\Gallery\\Http\\Controllers\\Api\\GalleryController,
         * and register the matching resource on the 'v1' JSON:API server.
         */
        #[Group('{$studly}', weight: 10)]
        final class {$studly}Controller
        {
            public function index(): DataResponse
            {
                return DataResponse::make([])
                    ->withServer('v1')
                    ->withMeta(['total' => 0]);
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
        use App\\JsonApi\\V1\\Middleware\\EnsureJsonApiMediaType;
        use Illuminate\\Support\\Facades\\Route;

        /**
         * {$studly} extension API routes.
         *
         * Registered centrally by the Pixely Extension SDK v2 route registrar.
         * Honours the strict JSON:API contract (Accept / Content-Type
         * application/vnd.api+json) via EnsureJsonApiMediaType.
         */
        Route::middleware([
            EnsureJsonApiMediaType::class,
            'auth:sanctum',
            'permission:{$id}.items.view',
        ])->group(function () {
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

    private function unitTestStub(string $studly, string $id): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        use App\\Extensions\\{$studly}\\{$studly}Extension;

        it('declares the {$id} extension manifest', function () {
            \$manifest = (new {$studly}Extension())->manifest();

            expect(\$manifest->id)->toBe('{$id}')
                ->and(\$manifest->name)->toBe('{$studly}')
                ->and(\$manifest->minimum_kernel_version)->toBe('1.0.0');
        });

        PHP;
    }

    private function functionalTestStub(string $studly, string $id): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        use Tests\\TestCase;

        uses(TestCase::class);

        it('requires authentication to list {$studly} items', function () {
            // Enable the {$id} extension before running this API feature test.
            \$this->getJson('/api/v1/{$id}')->assertUnauthorized();
        });

        PHP;
    }

    private function e2eTestStub(string $studly, string $id): string
    {
        return <<<TS
        import { expect, test } from '@playwright/test'

        test('logs in and opens the {$studly} administration screen', async ({ page }) => {
          const email = process.env.E2E_USER_EMAIL ?? 'test@example.com'
          const password = process.env.E2E_USER_PASSWORD ?? 'password'

          await page.goto('/login')
          const csrfResponsePromise = page.waitForResponse('/sanctum/csrf-cookie')
          await page.evaluate(async () => fetch('/sanctum/csrf-cookie', { credentials: 'include' }))
          const csrfResponse = await csrfResponsePromise
          expect(csrfResponse.status()).toBe(204)
          await expect.poll(async () => (await page.context().cookies()).some((cookie) => cookie.name === 'XSRF-TOKEN')).toBe(true)
          await page.locator('input[autocomplete="username"]').fill(email)
          await page.locator('input[autocomplete="current-password"]').fill(password)
          await page.locator('form button[type="submit"]').click()

          await page.goto('/admin/{$id}')

          await expect(page.getByRole('heading', { name: '{$studly}' })).toBeVisible()
        })

        TS;
    }

    private function frontendModelStub(string $studly): string
    {
        return <<<TS
        // {$studly} resource attributes as returned under `attributes`.
        // `id` and `type` are added by apiClient during JSON:API deserialization;
        // declare attribute fields here.
        export interface {$studly}Item {
          id: string
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
              const result = await apiClient.getCollection<{$studly}Item>('/{$id}')
              this.items = result.resources
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
        $this->newLine();
        $this->line('<comment>Remaining manual steps:</comment>');
        $this->line('1. Add a Vite/TS alias in vite.config.js and tsconfig.json:');
        $this->line("   \"@extensions/{$id}\": .../app/Extensions/{$studly}/resources/js");
        $this->line("2. Add the frontend route in resources/js/router/index.ts (frontend route registration remains explicit in S4).");
        $this->line("3. Add {$id}.items.view/manage/delete to database/seeders/RolePermissionSeeder.php (or rely on ExtensionPermissionSynchronizer at enable time).");
        $this->line("4. Run: docker compose exec app php artisan pixely:extension:migrate {$id}  (once you add migrations to Database/Migrations/).");
        $this->line("5. Run: docker compose exec app php artisan pixely:extension:migration-status {$id}");
        $this->line('6. Run: docker compose exec app php artisan pixely:extensions  to confirm discovery.');
        $this->line('7. API routes and admin navigation are registered automatically by SDK v2.');
        $this->line("8. (JSON:API) Register this extension's resource type in App\\JsonApi\\V1\\Server::allSchemas() when the first resource is implemented.");
    }
}
