<?php

declare(strict_types=1);

namespace App\Core\Updates\DTO;

use App\Core\Updates\Enums\UpdateChannel;
use DateTimeImmutable;
use DateTimeZone;

/** Immutable publication metadata for one package release. */
final readonly class ReleaseManifestRelease
{
    public function __construct(
        public string $version,
        public UpdateChannel $channel,
        public DateTimeImmutable $publishedAt,
        public ?string $releaseNotes = null,
    ) {
        ReleaseManifestValue::assertSemver($this->version, 'release.version');

        if ($this->publishedAt->format('P') !== '+00:00') {
            throw new \InvalidArgumentException('release.published_at must use UTC.');
        }

        if ($this->releaseNotes !== null) {
            ReleaseManifestValue::assertHttpsUrl($this->releaseNotes, 'release.release_notes');
        }
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        ReleaseManifestValue::assertKeys($data, ['version', 'channel', 'published_at', 'release_notes'], 'release');
        $channel = UpdateChannel::tryFrom(ReleaseManifestValue::requiredString($data, 'channel', 'release'));
        if ($channel === null) {
            throw new \InvalidArgumentException('release.channel is not supported.');
        }

        $publishedAt = ReleaseManifestValue::requiredString($data, 'published_at', 'release');
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?Z$/', $publishedAt) !== 1) {
            throw new \InvalidArgumentException('release.published_at must be an RFC 3339 UTC timestamp ending in Z.');
        }

        try {
            $date = new DateTimeImmutable($publishedAt, new DateTimeZone('UTC'));
            $dateErrors = DateTimeImmutable::getLastErrors();
            if (is_array($dateErrors) && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0)) {
                throw new \InvalidArgumentException('release.published_at is not a valid date.');
            }
        } catch (\Exception $exception) {
            throw new \InvalidArgumentException('release.published_at is not a valid date.', previous: $exception);
        }

        $releaseNotes = $data['release_notes'] ?? null;
        if ($releaseNotes !== null && !is_string($releaseNotes)) {
            throw new \InvalidArgumentException('release.release_notes must be a string or null.');
        }

        return new self(
            version: ReleaseManifestValue::requiredString($data, 'version', 'release'),
            channel: $channel,
            publishedAt: $date,
            releaseNotes: $releaseNotes,
        );
    }
}
