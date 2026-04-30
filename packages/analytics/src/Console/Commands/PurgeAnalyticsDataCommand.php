<?php

declare(strict_types=1);

namespace Capell\Analytics\Console\Commands;

use Capell\Analytics\Actions\PurgeAnalyticsDataAction;
use Illuminate\Console\Command;

final class PurgeAnalyticsDataCommand extends Command
{
    protected $signature = 'analytics:purge {--days= : Override analytics retention days}';

    protected $description = 'Delete old analytics records.';

    public function handle(): int
    {
        $retentionDays = $this->resolveRetentionDaysOption();
        $deletedRecords = PurgeAnalyticsDataAction::run($retentionDays);

        $this->info("Purged {$deletedRecords} analytics records.");

        return self::SUCCESS;
    }

    private function resolveRetentionDaysOption(): ?int
    {
        $daysOption = $this->option('days');

        if ($daysOption === null || $daysOption === '') {
            return null;
        }

        return (int) $daysOption;
    }
}
