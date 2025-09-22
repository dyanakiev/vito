<?php

namespace App\Actions\Site;

use App\Enums\BackupFileStatus;
use App\Enums\BackupStatus;
use App\Models\Backup;
use App\Models\BackupFile;
use App\Services\SSH\SSH;
use Illuminate\Support\Str;

class RunSiteBackup
{
    public function run(Backup $backup): BackupFile
    {
        $file = new BackupFile([
            'backup_id' => $backup->id,
            'name' => Str::of($backup->site->domain)->slug().'-'.now()->format('YmdHis'),
            'status' => BackupFileStatus::CREATING,
        ]);
        $file->save();

        dispatch(function () use ($file, $backup): void {
            $this->runBackup($file, $backup);
            $file->status = BackupFileStatus::CREATED;
            $file->save();

            if ($backup->status !== BackupStatus::RUNNING) {
                $backup->status = BackupStatus::RUNNING;
                $backup->save();
            }
        })->catch(function () use ($file, $backup): void {
            $backup->status = BackupStatus::FAILED;
            $backup->save();
            $file->status = BackupFileStatus::FAILED;
            $file->save();
        })->onQueue('ssh');

        return $file;
    }

    private function runBackup(BackupFile $file, Backup $backup): void
    {
        $server = $backup->server;
        $site = $backup->site;
        $storage = $backup->storage;
        $exclusions = $backup->exclusions ?? [];
        
        // Build exclusion arguments for tar
        $excludeArgs = [];
        foreach ($exclusions as $exclusion) {
            $excludeArgs[] = "--exclude={$exclusion}";
        }
        
        $excludeString = implode(' ', $excludeArgs);
        
        // Create backup command
        $backupPath = $site->path;
        $backupName = $file->name . '.tar.gz';
        $tempPath = "/tmp/{$backupName}";
        
        $command = "cd {$backupPath} && tar -czf {$tempPath} {$excludeString} .";
        
        // Execute backup command via SSH
        $ssh = new SSH($server);
        $ssh->execute($command);
        
        // Upload to storage
        $this->uploadToStorage($storage, $tempPath, $backupName, $site->domain);
        
        // Clean up temp file
        $ssh->execute("rm -f {$tempPath}");
    }

    private function uploadToStorage($storage, $tempPath, $backupName, $siteDomain): void
    {
        // This would contain the storage upload logic
        // Similar to the existing database backup upload logic
        // Implementation depends on the storage provider
    }
}
