<?php

namespace App\Console\Commands;

use App\Actions\Database\RunBackup;
use App\Actions\Site\RunSiteBackup;
use App\Enums\BackupStatus;
use App\Models\Backup;
use Illuminate\Console\Command;

class RunBackupCommand extends Command
{
    protected $signature = 'backups:run {interval}';

    protected $description = 'Run backup';

    public function handle(): void
    {
        $total = 0;

        Backup::query()
            ->where('interval', $this->argument('interval'))
            ->where('status', BackupStatus::RUNNING)
            ->chunk(100, function ($backups) use (&$total): void {
                /** @var Backup $backup */
                foreach ($backups as $backup) {
                    if ($backup->type === 'database') {
                        app(RunBackup::class)->run($backup);
                    } elseif ($backup->type === 'site') {
                        app(RunSiteBackup::class)->run($backup);
                    }
                    $total++;
                }
            });

        $this->info("{$total} backups started");
    }
}
