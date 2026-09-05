<?php

declare(strict_types=1);

namespace App\Core\Extensions\Translations;

/**
 * Optional contract for extensions that ship their own translation
 * files, discoverable by the Translations extension without any
 * hardcoded per-module knowledge.
 */
interface ExtensionTranslatableInterface
{
    /**
     * Absolute path to this extension's lang directory, expected to
     * contain one subfolder per locale (e.g. <path>/en/gallery.php).
     */
    public function translationsPath(): string;
}
