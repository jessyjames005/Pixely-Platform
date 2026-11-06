<?php

declare(strict_types=1);

namespace App\Extensions\CinemaMovie\Services;

use App\Core\Extensions\Configuration\ExtensionConfigurationRepositoryInterface;
use App\Extensions\CinemaMovie\CinemaMovieExtension;

final class CinemaMovieSettings
{
    public function __construct(
        private readonly ExtensionConfigurationRepositoryInterface $repository,
    ) {
    }

    /**
     * @return list<string>
     */
    public function supportedLocales(): array
    {
        $defaults = (new CinemaMovieExtension())->defaultConfiguration();
        $configuration = array_replace($defaults, $this->repository->load('cinema-movie'));
        $locales = $configuration['supported_locales'] ?? [];

        return array_values(array_unique(array_filter($locales, 'is_string')));
    }
}
