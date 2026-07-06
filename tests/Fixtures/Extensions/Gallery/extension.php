<?php

/**
 * Extension manifest file.
 *
 * This file defines the metadata required for Pixely
 * to discover and register this extension.
 */

return [
    /*
    | Name of the extension (unique identifier)
    */
    'name' => 'gallery',

    /*
    | Semantic version of the extension
    */
    'version' => '1.0.0',

    /*
    | Main entry class of the extension
    */
    'class' => App\Extensions\Gallery\GalleryExtension::class,
];
