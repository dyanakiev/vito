<?php

namespace App\Http\Controllers;

use App\Actions\Site\ManageSiteBackup;
use App\Actions\Site\RunSiteBackup;
use App\Http\Resources\BackupFileResource;
use App\Http\Resources\BackupResource;
use App\Models\Backup;
use App\Models\BackupFile;
use App\Models\Server;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Patch;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('servers/{server}/sites/{site}/backups')]
#[Middleware(['auth', 'has-project'])]
class SiteBackupController extends Controller
{
    #[Get('/', name: 'site-backups')]
    public function index(Server $server, Site $site): Response
    {
        $this->authorize('viewAny', [Backup::class, $server]);

        return Inertia::render('site-backups/index', [
            'backups' => BackupResource::collection(
                $site->backups()->with(['lastFile', 'site', 'storage'])->simplePaginate(config('web.pagination_size'))
            ),
        ]);
    }

    #[Get('/{backup}', name: 'site-backups.show')]
    public function show(Server $server, Site $site, Backup $backup): JsonResponse
    {
        $this->authorize('view', $backup);

        return response()->json([
            'backup' => BackupResource::make($backup->load(['storage', 'site'])),
            'files' => BackupFileResource::collection($backup->files()->simplePaginate(config('web.pagination_size'))),
        ]);
    }

    #[Post('/', name: 'site-backups.store')]
    public function store(Request $request, Server $server, Site $site): RedirectResponse
    {
        $this->authorize('create', [Backup::class, $server]);

        $data = $request->all();
        $data['site'] = $site->id; // Set the site ID from the route parameter

        app(ManageSiteBackup::class)->create($server, $data);

        return back()
            ->with('info', 'Site backup is being created...');
    }

    #[Patch('/{backup}', name: 'site-backups.update')]
    public function update(Request $request, Server $server, Site $site, Backup $backup): RedirectResponse
    {
        $this->authorize('update', $backup);

        app(ManageSiteBackup::class)->update($backup, $request->all());

        return back()
            ->with('success', 'Site backup updated successfully.');
    }

    #[Post('/{backup}/run', name: 'site-backups.run')]
    public function run(Server $server, Site $site, Backup $backup): RedirectResponse
    {
        $this->authorize('create', [BackupFile::class, $backup]);

        app(RunSiteBackup::class)->run($backup);

        return back()
            ->with('info', 'Site backup is being created...');
    }

    #[Delete('/{backup}', name: 'site-backups.destroy')]
    public function destroy(Server $server, Site $site, Backup $backup): RedirectResponse
    {
        $this->authorize('delete', $backup);

        app(ManageSiteBackup::class)->delete($backup);

        return back()
            ->with('warning', 'Site backup is being deleted...');
    }
}
