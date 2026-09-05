<?php

declare(strict_types=1);

use App\Extensions\Translations\Services\TranslationRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->modulePath = storage_path('app/test-translations-' . uniqid('', true));
    File::ensureDirectoryExists($this->modulePath . '/en');
    File::ensureDirectoryExists($this->modulePath . '/fr');

    file_put_contents($this->modulePath . '/en/widgets.php', "<?php\nreturn ['title' => 'Widgets', 'empty' => 'No widgets'];");
    file_put_contents($this->modulePath . '/fr/widgets.php', "<?php\nreturn ['title' => 'Widgets'];"); // 'empty' missing, 'title' left untranslated (identical to en)

    $this->repository = app(TranslationRepository::class);
});

afterEach(function () {
    File::deleteDirectory($this->modulePath);
});

it('discovers core as an always-present translatable module', function () {
    $modules = $this->repository->discoverModules();

    expect($modules)->toHaveKey('core');
});

it('lists available locales for a module', function () {
    $locales = $this->repository->availableLocales($this->modulePath);

    expect($locales)->toContain('en', 'fr');
});

it('lists available groups for a locale', function () {
    $groups = $this->repository->availableGroups($this->modulePath, 'en');

    expect($groups)->toBe(['widgets']);
});

it('reads a group flattened to dot notation', function () {
    $entries = $this->repository->readGroup($this->modulePath, 'en', 'widgets');

    expect($entries)->toBe(['title' => 'Widgets', 'empty' => 'No widgets']);
});

it('flattens nested translation arrays with dot notation', function () {
    file_put_contents($this->modulePath . '/en/nested.php', "<?php\nreturn ['CAlert' => ['one' => 'the alert', 'all' => 'all alerts']];");

    $entries = $this->repository->readGroup($this->modulePath, 'en', 'nested');

    expect($entries)->toBe(['CAlert.one' => 'the alert', 'CAlert.all' => 'all alerts']);
});

it('writes a flattened group back as nested PHP array and reads it back the same', function () {
    $this->repository->writeGroup($this->modulePath, 'en', 'new-group', [
        'CAlert.one' => 'the alert',
        'simple' => 'value',
    ]);

    $entries = $this->repository->readGroup($this->modulePath, 'en', 'new-group');

    expect($entries)->toBe([
        'CAlert.one' => 'the alert',
        'simple' => 'value',
    ]);
});

it('compares target against reference and flags missing keys as suspect', function () {
    $result = $this->repository->compare($this->modulePath, 'en', 'fr', 'widgets');

    $byKey = collect($result['entries'])->keyBy('key');

    expect($byKey['empty']['target'])->toBeNull();
    expect($byKey['empty']['suspect'])->toBeTrue();
});

it('flags a target value identical to its key as suspect', function () {
    file_put_contents($this->modulePath . '/en/flagged.php', "<?php\nreturn ['greeting' => 'greeting'];");

    $result = $this->repository->compare($this->modulePath, 'en', 'en', 'flagged');

    expect($result['entries'][0]['suspect'])->toBeTrue();
});

it('computes a completion percentage based on non-suspect entries', function () {
    $result = $this->repository->compare($this->modulePath, 'en', 'fr', 'widgets');

    // 1 of 2 keys filled (title present and different-looking, empty missing)
    expect($result['completion'])->toBe(50.0);
});

it('rejects a locale segment attempting path traversal', function () {
    $this->repository->readGroup($this->modulePath, '../../etc', 'widgets');
})->throws(\Symfony\Component\HttpKernel\Exception\HttpException::class);

it('rejects a group segment attempting path traversal', function () {
    $this->repository->readGroup($this->modulePath, 'en', '../../etc/passwd');
})->throws(\Symfony\Component\HttpKernel\Exception\HttpException::class);
