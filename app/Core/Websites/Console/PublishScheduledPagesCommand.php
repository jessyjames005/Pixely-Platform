<?php

declare(strict_types=1);

namespace App\Core\Websites\Console;

use App\Core\Websites\Persistence\Models\PageRecord;
use Illuminate\Console\Command;

/**
 * Publishes website pages whose scheduled publication time has elapsed.
 *
 * Intended to be run by the scheduler to transition scheduled pages
 * to the published state.
 */
final class PublishScheduledPagesCommand extends Command
{
    protected $signature = 'website:publish-scheduled';
    protected $description = 'Publish website pages whose scheduled publish time has passed.';

    public function handle(): int
    {
        $count = PageRecord::query()
            ->where('status', 'scheduled')
            ->whereNotNull('publish_at')
            ->where('publish_at', '<=', now())
            ->update(['status' => 'published', 'published_at' => now()]);

        $this->info("Published {$count} scheduled page(s).");

        return self::SUCCESS;
    }
}
