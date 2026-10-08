<?php

declare(strict_types=1);

namespace App\Core\Users\Services;

use App\Core\Users\Models\UserFavorite;
use App\Core\Users\Models\UserHistoryEntry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

/**
 * Provides generic favorites and history capabilities for the User Space.
 *
 * Extensions provide resource_type/resource_id values; the Core never needs
 * to know which extension owns the referenced resource.
 */
final class UserEngagementService
{
    /**
     * @param array<string, mixed>|null $metadata
     */
    public function addFavorite(
        int $userId,
        string $resourceType,
        string $resourceId,
        ?array $metadata = null,
    ): UserFavorite {
        $this->validateReference($resourceType, $resourceId);

        return UserFavorite::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
            ],
            ['metadata' => $metadata],
        );
    }

    /**
     * Remove a favorite if it belongs to the current user.
     */
    public function removeFavorite(int $userId, string $resourceType, string $resourceId): bool
    {
        return UserFavorite::query()
            ->where('user_id', $userId)
            ->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->delete() > 0;
    }

    /**
     * @return LengthAwarePaginator<UserFavorite>
     */
    public function favorites(int $userId, ?string $resourceType = null, int $perPage = 20): LengthAwarePaginator
    {
        return UserFavorite::query()
            ->where('user_id', $userId)
            ->when($resourceType !== null, fn ($query) => $query->where('resource_type', $resourceType))
            ->latest('updated_at')
            ->paginate($perPage);
    }

    /**
     * Record a user interaction. Repeated interactions remain separate so the
     * history can represent actual chronology.
     *
     * @param array<string, mixed>|null $metadata
     */
    public function recordHistory(
        int $userId,
        string $resourceType,
        string $resourceId,
        string $action = 'view',
        ?array $metadata = null,
    ): UserHistoryEntry {
        $this->validateReference($resourceType, $resourceId);

        return UserHistoryEntry::query()->create([
            'user_id' => $userId,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'action' => $action,
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);
    }

    /**
     * @return LengthAwarePaginator<UserHistoryEntry>
     */
    public function history(int $userId, ?string $resourceType = null, int $perPage = 20): LengthAwarePaginator
    {
        return UserHistoryEntry::query()
            ->where('user_id', $userId)
            ->when($resourceType !== null, fn ($query) => $query->where('resource_type', $resourceType))
            ->latest('occurred_at')
            ->paginate($perPage);
    }

    /**
     * @return array{resource_type: string, resource_id: string}
     */
    public function reference(string $resourceType, string $resourceId): array
    {
        $this->validateReference($resourceType, $resourceId);

        return [
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
        ];
    }

    private function validateReference(string $resourceType, string $resourceId): void
    {
        abort_if($resourceType === '' || strlen($resourceType) > 100, 422, 'Invalid resource type.');
        abort_if($resourceId === '' || strlen($resourceId) > 191, 422, 'Invalid resource identifier.');
    }
}
