<?php

namespace App\Models;

use App\Jobs\GenerateTrackWaveform;
use App\Support\LocalAudioFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Track extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'title',
        'track_number',
        'recorded_at',
        'notes',
        'storage_path',
        'original_filename',
    ];

    protected $casts = [
        'duration_seconds' => 'integer',
        'file_size_bytes' => 'integer',
        'bitrate' => 'integer',
        'recorded_at' => 'date',
        'waveform_generated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (Track $track) {
            if ($track->isDirty('storage_path') && $track->storage_path) {
                // Fill duration, size, etc.
                $track->populateAudioMetadata();
            }
        });

        static::saved(function (Track $track) {
            if ($track->storage_path && ($track->wasRecentlyCreated || $track->wasChanged('storage_path'))) {
                // For now: run synchronously (no queue)
                GenerateTrackWaveform::dispatchSync($track);
            }
        });
    }

    public function populateAudioMetadata(): void
    {
        $disk = Storage::disk(config('filesystems.default'));
        $relativePath = $this->storage_path;

        if (! $relativePath) {
            return;
        }

        try {
            LocalAudioFile::withPath($disk, $relativePath, function (string $localPath): void {
                $getID3 = new \getID3;
                $info = $getID3->analyze($localPath);

                $this->duration_seconds = isset($info['playtime_seconds'])
                    ? (int) round($info['playtime_seconds'])
                    : null;

                $this->file_size_bytes = $info['filesize'] ?? @filesize($localPath) ?: null;
                $this->format = $info['fileformat'] ?? null;

                if (isset($info['bitrate'])) {
                    $this->bitrate = (int) $info['bitrate'];
                }

                $this->original_filename = $this->original_filename ?: basename($this->storage_path);
            });
        } catch (\Throwable $e) {
            Log::warning('Audio metadata extraction failed for track '.$this->id.': '.$e->getMessage());
        }
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
