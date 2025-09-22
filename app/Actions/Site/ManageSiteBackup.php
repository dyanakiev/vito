<?php

namespace App\Actions\Site;

use App\Enums\BackupFileStatus;
use App\Enums\BackupStatus;
use App\Models\Backup;
use App\Models\Server;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ManageSiteBackup
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function create(Server $server, array $input): Backup
    {
        $this->validate($server, $input);

        $backup = new Backup([
            'type' => 'site',
            'server_id' => $server->id,
            'site_id' => $input['site'] ?? null,
            'storage_id' => $input['storage'],
            'interval' => $input['interval'] == 'custom' ? $input['custom_interval'] : $input['interval'],
            'keep_backups' => $input['keep'],
            'exclusions' => $input['exclusions'] ?? [],
            'status' => BackupStatus::RUNNING,
        ]);
        $backup->save();

        app(RunSiteBackup::class)->run($backup);

        return $backup;
    }

    public function update(Backup $backup, array $input): void
    {
        $backup->interval = $input['interval'] == 'custom' ? $input['custom_interval'] : $input['interval'];
        $backup->keep_backups = $input['keep'];
        $backup->exclusions = $input['exclusions'] ?? [];
        $backup->save();
    }

    public function delete(Backup $backup): void
    {
        $backup->status = BackupStatus::DELETING;
        $backup->save();

        dispatch(function () use ($backup): void {
            $files = $backup->files;
            foreach ($files as $file) {
                $file->status = BackupFileStatus::DELETING;
                $file->save();

                $file->deleteFile();
            }

            $backup->delete();
        })->onQueue('ssh');
    }

    public function stop(Backup $backup): void
    {
        $backup->status = BackupStatus::STOPPED;
        $backup->save();
    }

    private function validate(Server $server, array $input): void
    {
        Validator::make($input, [
            'site' => [
                'required',
                Rule::exists('sites', 'id')->where('server_id', $server->id),
            ],
            'storage' => [
                'required',
                Rule::exists('storage_providers', 'id'),
            ],
            'interval' => [
                'required',
                Rule::in(array_keys(config('core.cronjob_intervals'))),
            ],
            'custom_interval' => [
                'required_if:interval,custom',
                'string',
            ],
            'keep' => [
                'required',
                'integer',
                'min:1',
                'max:100',
            ],
            'exclusions' => [
                'array',
            ],
            'exclusions.*' => [
                'string',
                'max:255',
            ],
        ])->validate();
    }
}
