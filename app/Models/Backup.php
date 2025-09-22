<?php

namespace App\Models;

use App\Enums\BackupStatus;
use Database\Factories\BackupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Site;
use App\Models\Server;
use App\Models\StorageProvider;
use App\Models\Database;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string $type
 * @property int $server_id
 * @property int $storage_id
 * @property int $database_id
 * @property int $site_id
 * @property string $interval
 * @property int $keep_backups
 * @property array $exclusions
 * @property BackupStatus $status
 * @property Server $server
 * @property StorageProvider $storage
 * @property Database $database
 * @property Site $site
 * @property BackupFile[] $files
 */
class Backup extends AbstractModel
{
    /** @use HasFactory<BackupFactory> */
    use HasFactory;

    protected $fillable = [
        'type',
        'server_id',
        'storage_id',
        'database_id',
        'site_id',
        'interval',
        'keep_backups',
        'exclusions',
        'status',
    ];

    protected $casts = [
        'server_id' => 'integer',
        'storage_id' => 'integer',
        'database_id' => 'integer',
        'site_id' => 'integer',
        'keep_backups' => 'integer',
        'exclusions' => 'array',
        'status' => BackupStatus::class,
    ];

    public static function boot(): void
    {
        parent::boot();

        static::deleting(function ($backup): void {
            /** @var Backup $backup */
            $backup->files()->each(function ($file): void {
                /** @var BackupFile $file */
                $file->delete();
            });
        });
    }

    public function isCustomInterval(): bool
    {
        $intervals = array_keys(config('core.cronjob_intervals'));
        $intervals = array_filter($intervals, fn ($interval): bool => $interval !== 'custom');

        return ! in_array($this->interval, $intervals);
    }

    /**
     * @return BelongsTo<Server, covariant $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * @return BelongsTo<StorageProvider, covariant $this>
     */
    public function storage(): BelongsTo
    {
        return $this->belongsTo(StorageProvider::class, 'storage_id');
    }

    /**
     * @return BelongsTo<Database, covariant $this>
     */
    public function database(): BelongsTo
    {
        return $this->belongsTo(Database::class)->withTrashed();
    }

    /**
     * @return BelongsTo<Site, covariant $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class)->withTrashed();
    }

    /**
     * @return HasMany<BackupFile, covariant $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(BackupFile::class, 'backup_id');
    }

    /**
     * @return HasOne<BackupFile, covariant $this>
     */
    public function lastFile(): HasOne
    {
        return $this->hasOne(BackupFile::class, 'backup_id')->latest();
    }
}
