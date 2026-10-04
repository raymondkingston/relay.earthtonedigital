<?php

namespace App\Jobs;

use App\Models\Track;
use App\Support\LocalAudioFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateTrackWaveform implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Track $track;

    /**
     * Create a new job instance.
     */
    public function __construct(Track $track)
    {
        // Make sure we have a fresh, serializable model instance
        $this->track = $track;
    }

    /**
     * Execute the job.
     */
    public function handle(): bool
    {
        // Bail if there's no stored audio
        if (! $this->track->storage_path) {
            return false;
        }

        $disk = Storage::disk(config('filesystems.default'));
        $sourcePath = $this->track->storage_path;

        try {
            return LocalAudioFile::withPath($disk, $sourcePath, function (string $localPath) use ($disk): bool {
                $waveformLocalPath = tempnam(sys_get_temp_dir(), 'relay_waveform_');
                if ($waveformLocalPath === false) {
                    throw new \RuntimeException('Unable to create temporary waveform file.');
                }
                try {
                    $project = $this->track->project;
                    $artist = $project?->artist;

                    // Build slugs for path
                    $artistSlug = $artist->slug ?? \Str::slug($artist->name ?? 'artist-'.$artist?->id ?? 'unknown');
                    $projectSlug = $project->slug ?? \Str::slug($project->title ?? 'project-'.$project?->id ?? 'unknown');

                    // Where the waveform should live on your app disk
                    $waveformRelativePath = "media/{$artistSlug}/{$projectSlug}/waveforms/track-{$this->track->id}.png";

                    // Build ffmpeg command
                    // - Mono waveform
                    // - 800x120 image
                    // - Emerald-ish line color
                    $input = escapeshellarg($localPath);
                    $output = escapeshellarg($waveformLocalPath);

                    $cmd = "ffmpeg -y -i {$input} -filter_complex "
                        .'"aformat=channel_layouts=mono,showwavespic=s=800x120:colors=dadadaff" '
                        ."-frames:v 1 -c:v png -f image2 {$output} 2>&1";

                    exec($cmd, $outputLines, $exitCode);

                    if ($exitCode !== 0 || ! file_exists($waveformLocalPath)) {
                        Log::warning('ffmpeg waveform generation failed for track '.$this->track->id, [
                            'exit_code' => $exitCode,
                            'output' => $outputLines,
                        ]);

                        return false;
                    }

                    // Store waveform PNG on your disk
                    $fileContents = file_get_contents($waveformLocalPath);

                    if ($fileContents === false || ! $disk->put($waveformRelativePath, $fileContents, ['visibility' => 'public'])) {
                        throw new \RuntimeException('Unable to store waveform image.');
                    }

                    // Persist path + timestamp on the track
                    $this->track->waveform_image_path = $waveformRelativePath;
                    $this->track->waveform_generated_at = now();
                    $this->track->saveQuietly();

                    return true;
                } finally {
                    if (is_file($waveformLocalPath)) {
                        unlink($waveformLocalPath);
                    }
                }
            });
        } catch (\Throwable $e) {
            Log::warning('Waveform generation failed for track '.$this->track->id.': '.$e->getMessage());

            return false;
        }
    }
}
