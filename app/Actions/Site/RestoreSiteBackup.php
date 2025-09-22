<?php

namespace App\Actions\Site;

use App\Enums\BackupFileStatus;
use App\Models\BackupFile;
use App\Models\Site;
use App\Models\Server;
use App\Services\SSH\SSH;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RestoreSiteBackup
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function restore(BackupFile $backupFile, array $input): void
    {
        $this->validate($backupFile->backup->server, $input);

        /** @var Site $site */
        $site = Site::query()->findOrFail($input['site']);
        $backupFile->status = BackupFileStatus::RESTORING;
        $backupFile->restored_to = $site->domain;
        $backupFile->save();

        dispatch(function () use ($backupFile, $site): void {
            $this->restoreBackup($backupFile, $site);
            $backupFile->status = BackupFileStatus::RESTORED;
            $backupFile->restored_at = now();
            $backupFile->save();
        })->catch(function () use ($backupFile): void {
            $backupFile->status = BackupFileStatus::RESTORE_FAILED;
            $backupFile->save();
        })->onQueue('ssh');
    }

    private function restoreBackup(BackupFile $backupFile, Site $site): void
    {
        $server = $backupFile->backup->server;
        $storage = $backupFile->backup->storage;
        
        // Download backup file from storage
        $backupName = $backupFile->name . '.tar.gz';
        $tempPath = "/tmp/{$backupName}";
        
        $this->downloadFromStorage($storage, $backupName, $tempPath, $site->domain);
        
        // Extract backup to site directory
        $sitePath = $site->path;
        $command = "cd {$sitePath} && tar -xzf {$tempPath}";
        
        $ssh = new SSH($server);
        $ssh->execute($command);
        
        // Clean up temp file
        $ssh->execute("rm -f {$tempPath}");
    }

    private function downloadFromStorage($storage, $backupName, $tempPath, $siteDomain): void
    {
        // This would contain the storage download logic
        // Similar to the existing database backup download logic
        // Implementation depends on the storage provider
    }

    private function validate(Server $server, array $input): void
    {
        Validator::make($input, [
            'site' => [
                'required',
                Rule::exists('sites', 'id')->where('server_id', $server->id),
            ],
        ])->validate();
    }
}
