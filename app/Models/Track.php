<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Jobs\GenerateTrackWaveform;

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
        'notes',
        'storage_path',
        'original_filename',
    ];

    protected $casts = [
        'duration_seconds' => 'integer',
        'file_size_bytes'  => 'integer',
        'bitrate'          => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Track $track) {
            // Only (re)analyze when the audio path changes
            if ($track->isDirty('storage_path') && $track->storage_path) {
                $track->populateAudioMetadata();
            }
        });

        static::saved(function (Track $track) {
            if ($track->wasChanged('storage_path') && $track->storage_path) {
                GenerateTrackWaveform::dispatch($track);
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

        $localPath = null;
        $deleteTmp = false;

        // Local disk (public) – we can access the actual file path directly
        if (method_exists($disk, 'path')) {
            $localPath = $disk->path($relativePath);
        } else {
            // e.g. S3 – we need to download to a temp file
            $tmpFile = tempnam(sys_get_temp_dir(), 'track_');

            $stream = $disk->readStream($relativePath);

            if ($stream === false) {
                return;
            }

            $contents = stream_get_contents($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }

            file_put_contents($tmpFile, $contents);

            $localPath = $tmpFile;
            $deleteTmp = true;
        }

        if (! $localPath || ! file_exists($localPath)) {
            return;
        }

        try {
            $getID3 = new \getID3;
            $info = $getID3->analyze($localPath);

            // Duration in seconds
            $this->duration_seconds = isset($info['playtime_seconds'])
                ? (int) round($info['playtime_seconds'])
                : null;

            // File size
            $this->file_size_bytes = $info['filesize'] ?? @filesize($localPath) ?: null;

            // Format (e.g. mp3, flac, etc.)
            $this->format = $info['fileformat'] ?? null;

            // Bitrate (bits per second)
            if (isset($info['bitrate'])) {
                $this->bitrate = (int) $info['bitrate'];
            }

            // Original filename (fallback to basename of stored path)
            $this->original_filename =
                $info['filename'] ?? basename($this->storage_path);
        } catch (\Throwable $e) {
            // Fail quietly – you may want to log this later.
        } finally {
            if ($deleteTmp && isset($tmpFile) && file_exists($tmpFile)) {
                @unlink($tmpFile);
            }
        }
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
