<?php

namespace App\Jobs;

use App\Models\Track;
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
    public function handle(): void
    {
        // Bail if there's no stored audio
        if (! $this->track->storage_path) {
            return;
        }

        $disk = Storage::disk(config('filesystems.default'));
        $sourcePath = $this->track->storage_path;

        // Resolve local path or download to a temp file (for S3, etc.)
        $localPath = null;
        $deleteTmpAudio = false;

        if (method_exists($disk, 'path')) {
            // Local/public disk
            $localPath = $disk->path($sourcePath);
        } else {
            // Remote disk (e.g. S3) – copy to temp file
            $tmpFile = tempnam(sys_get_temp_dir(), 'waveform_audio_');

            $stream = $disk->readStream($sourcePath);
            if ($stream === false) {
                return;
            }

            $contents = stream_get_contents($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }

            file_put_contents($tmpFile, $contents);

            $localPath = $tmpFile;
            $deleteTmpAudio = true;
        }

        if (! $localPath || ! file_exists($localPath)) {
            return;
        }

        try {
            $project = $this->track->project;
            $artist  = $project?->artist;

            // Build slugs for path
            $artistSlug = $artist->slug ?? \Str::slug($artist->name ?? 'artist-'.$artist?->id ?? 'unknown');
            $projectSlug = $project->slug ?? \Str::slug($project->title ?? 'project-'.$project?->id ?? 'unknown');

            // Where the waveform should live on your app disk
            $waveformRelativePath = "media/{$artistSlug}/{$projectSlug}/waveforms/track-{$this->track->id}.png";

            // Local temp path where ffmpeg will write
            $tmpWaveformDir = storage_path('app/tmp/waveforms');
            if (! is_dir($tmpWaveformDir)) {
                mkdir($tmpWaveformDir, 0755, true);
            }

            $waveformLocalPath = $tmpWaveformDir.'/track-'.$this->track->id.'.png';

            // Build ffmpeg command
            // - Mono waveform
            // - 800x120 image
            // - Emerald-ish line color
            $input  = escapeshellarg($localPath);
            $output = escapeshellarg($waveformLocalPath);

            $cmd = "ffmpeg -y -i {$input} -filter_complex "
                . "\"aformat=channel_layouts=mono,showwavespic=s=800x120:colors=dadadaff\" "
                . "-frames:v 1 {$output} 2>&1";

            exec($cmd, $outputLines, $exitCode);

            if ($exitCode !== 0 || ! file_exists($waveformLocalPath)) {
                Log::warning('ffmpeg waveform generation failed for track '.$this->track->id, [
                    'exit_code' => $exitCode,
                    'output'    => $outputLines,
                ]);
                return;
            }

            // Store waveform PNG on your disk
            $fileContents = file_get_contents($waveformLocalPath);

            $disk->put($waveformRelativePath, $fileContents, [
                'visibility' => 'public',
            ]);

            // Persist path + timestamp on the track
            $this->track->waveform_image_path = $waveformRelativePath;
            $this->track->waveform_generated_at = now();
            $this->track->save();

        } catch (\Throwable $e) {
            Log::warning('Waveform generation failed for track '.$this->track->id.': '.$e->getMessage());
        } finally {
            // Clean up temp files
            if ($deleteTmpAudio && isset($tmpFile) && file_exists($tmpFile)) {
                @unlink($tmpFile);
            }

            if (isset($waveformLocalPath) && file_exists($waveformLocalPath)) {
                @unlink($waveformLocalPath);
            }
        }
    }
}
