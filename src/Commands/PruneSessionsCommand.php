<?php

declare(strict_types=1);

namespace WillVincent\SessionManager\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use WillVincent\SessionManager\SessionManager;

final class PruneSessionsCommand extends Command
{
    protected $signature = 'session-manager:prune-sessions
        {--ttl= : Session TTL in minutes (defaults to session.lifetime)}
        {--dry-run : Show how many sessions would be deleted without deleting them}';

    protected $description = 'Prune expired session records based on session TTL';

    public function handle(): int
    {
        $ttl = $this->option('ttl');
        $ttlMinutes = $ttl !== null && is_numeric($ttl)
            ? (int) $ttl
            : SessionManager::sessionLifetime();

        $cutoffTimestamp = now()
            ->subMinutes($ttlMinutes)
            ->getTimestamp();

        $query = DB::table('sessions')
            ->where('last_activity', '<', $cutoffTimestamp);

        $count = (clone $query)->count();

        if ($this->option('dry-run')) {
            $this->info(sprintf(
                '[DRY RUN] %d expired sessions would be pruned.',
                $count
            ));

            return self::SUCCESS;
        }

        if ($count === 0) {
            $this->info('No expired sessions found.');

            return self::SUCCESS;
        }

        $deleted = $query->delete();

        $this->info(sprintf(
            'Pruned %d expired session(s).',
            $deleted
        ));

        return self::SUCCESS;
    }
}
