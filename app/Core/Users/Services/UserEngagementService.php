<?php

declare(strict_types=1);

namespace App\Core\Users\Services;

use App\Core\Users\Models\UserFavorite;
use App\Core\Users\Models\UserHistoryEntry;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/** Owns User Space favorite/history persistence and user-isolation rules. */
final class UserEngagementService
{
    public function favorites(User $user, ?string $resourceType = null, int $perPage = 20): LengthAwarePaginator
    {
        return UserFavorite::query()
            ->where('user_id', $user->getKey())
            ->when($resourceType, fn($query) => $query->where('resource_type', $resourceType))
            ->latest('id')
            ->paginate($perPage);
    }

    public function addFavorite(User $user, string $resourceType, string $resourceId, ?array $metadata = null): UserFavorite
    {
        return DB::transaction(function () use ($user, $resourceType, $resourceId, $metadata): UserFavorite {
            return UserFavorite::query()->updateOrCreate(
                [
                    'user_id' => $user->getKey(),
                    'resource_type' => $resourceType,
                    'resource_id' => $resourceId,
                ],
                [
                    'metadata' => $metadata,
                ],
            );
        });
    }

    public function removeFavorite(User $user, string $resourceType, string $resourceId): bool
    {
        return UserFavorite::query()
            ->where('user_id', $user->getKey())
            ->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->delete() > 0;
    }

    public function history(User $user, ?string $resourceType = null, int $perPage = 20): LengthAwarePaginator
    {
        return UserHistoryEntry::query()
            ->where('user_id', $user->getKey())
            ->when($resourceType, fn($query) => $query->where('resource_type', $resourceType))
            ->latest('occurred_at')
            ->paginate($perPage);
    }

    public function recordHistory(User $user, string $resourceType, string $resourceId, string $action, ?array $metadata = null): UserHistoryEntry
    {
        return UserHistoryEntry::query()->create([
            'user_id' => $user->getKey(),
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'action' => $action,
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);
    }
}
