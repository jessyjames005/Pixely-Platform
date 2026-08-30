<?php

declare(strict_types=1);

namespace App\Core\Extensions\Audit;

use Illuminate\Database\Eloquent\Model;

/**
 * An append-only record of a sensitive extension lifecycle action.
 *
 * No update/delete route exists for this model anywhere in the
 * application — it is a write-once audit trail by design.
 */
final class ExtensionAuditLog extends Model
{
    protected $fillable = ['user_id', 'extension_id', 'action', 'version', 'details'];

    protected $casts = [
        'details' => 'array',
    ];
}
