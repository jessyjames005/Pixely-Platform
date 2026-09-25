<?php

declare(strict_types=1);

return [
    /**
     * Current Pixely Platform Kernel version.
     */
    'kernel_version' => '1.0.0',

    /**
     * Locales available across the platform.
     *
     * The 'code' is the value stored in settings and sent to the
     * frontend; 'label' is the human-readable name.
     */
    'locales' => [
        ['code' => 'en', 'label' => 'English'],
        ['code' => 'fr', 'label' => 'Français'],
    ],

    'default_locale' => 'en',
];
