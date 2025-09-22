<?php

namespace App\Http\Controllers;

use App\Actions\Site\RestoreSiteBackup;
use App\Http\Resources\BackupFileResource;
use App\Http\Resources\BackupResource;
use App\Models\Backup;
use App\Models\BackupFile;
use App\Models\Server;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('servers/{server}/sites/{site}/backups/{backup}/files')]
#[Middleware(['auth', 'has-project'])]
class SiteBackupFileController extends Controller
{
    #[Get('/', name: 'site-backup-files')]
    public function index(Server $server, Site $site, Backup $backup): Response
    {
        $this->authorize('viewAny', [BackupFile::class, $backup]);

        return Inertia::render('site-backups/files', [
            'backup' => BackupResource::make($backup->load(['storage', 'site'])),
            'files' => BackupFileResource::collection(
                $backup->files()->with('backup')->latest()->simplePaginate(config('web.pagination_size'))
            ),
        ]);
    }

    #[Post('/{backupFile}/restore', name: 'site-backup-files.restore')]
    public function restore(Request $request, Server $server, Site $site, Backup $backup, BackupFile $backupFile): RedirectResponse
    {
        $this->authorize('update', $backup);

        app(RestoreSiteBackup::class)->restore($backupFile, $request->input());

        return back()
            ->with('info', 'Site backup is being restored...');
    }

    #[Delete('/{backupFile}', name: 'site-backup-files.destroy')]
    public function destroy(Server $server, Site $site, Backup $backup, BackupFile $backupFile): RedirectResponse
    {
        $this->authorize('delete', $backupFile);

        $backupFile->deleteFile();

        return back()
            ->with('success', 'File deleted successfully.');
    }
}
