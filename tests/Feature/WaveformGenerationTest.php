<?php

namespace Tests\Feature;

use App\Jobs\GenerateTrackWaveform;
use App\Models\Artist;
use App\Models\Project;
use App\Models\Track;
use App\Support\LocalAudioFile;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class WaveformGenerationTest extends TestCase
{
    use RefreshDatabase;

    private function audio(): string
    {
        $data = str_repeat(pack('v', 1000), 8000);

        return 'RIFF'.pack('V', 36 + strlen($data)).'WAVEfmt '.pack('VvvVVvv', 16, 1, 1, 8000, 16000, 2, 16).'data'.pack('V', strlen($data)).$data;
    }

    private function track(): Track
    {
        $artist = Artist::create(['name' => 'Artist', 'slug' => 'artist']);
        $project = Project::create(['artist_id' => $artist->id, 'title' => 'Project', 'slug' => 'project']);

        return Track::withoutEvents(fn () => Track::create([
            'project_id' => $project->id, 'title' => 'Track', 'storage_path' => 'audio/track.wav',
        ]));
    }

    private function remoteDisk(): AwsS3V3Adapter
    {
        $disk = Mockery::mock(AwsS3V3Adapter::class);
        $disk->shouldReceive('getAdapter')->andReturn(Mockery::mock(\League\Flysystem\AwsS3V3\AwsS3V3Adapter::class));
        $disk->shouldNotReceive('path');
        $disk->shouldReceive('readStream')->with('audio/track.wav')->andReturnUsing(function () {
            $stream = fopen('php://temp', 'w+b');
            fwrite($stream, $this->audio());
            rewind($stream);

            return $stream;
        });
        Storage::shouldReceive('disk')->andReturn($disk);

        return $disk;
    }

    public function test_s3_audio_is_downloaded_for_metadata_and_waveform_generation(): void
    {
        $track = $this->track();
        $disk = $this->remoteDisk();
        $disk->shouldReceive('put')->once()->with(
            "media/artist/project/waveforms/track-{$track->id}.png",
            Mockery::on(fn ($bytes) => str_starts_with($bytes, "\x89PNG\r\n\x1a\n")),
            ['visibility' => 'public'],
        )->andReturn(true);

        $track->populateAudioMetadata();
        $this->assertSame(1, $track->duration_seconds);
        $this->assertSame('track.wav', $track->original_filename);
        $this->assertTrue((new GenerateTrackWaveform($track))->handle());
        $this->assertNotNull($track->fresh()->waveform_image_path);
    }

    public function test_failed_upload_does_not_mark_waveform_generated(): void
    {
        $track = $this->track();
        $this->remoteDisk()->shouldReceive('put')->once()->andReturn(false);
        $this->assertFalse((new GenerateTrackWaveform($track))->handle());
        $this->assertNull($track->fresh()->waveform_image_path);
        $this->assertNull($track->fresh()->waveform_generated_at);
    }

    public function test_remote_temporary_file_is_removed_when_processing_throws(): void
    {
        $disk = $this->remoteDisk();
        $temporaryPath = null;
        try {
            LocalAudioFile::withPath($disk, 'audio/track.wav', function ($path) use (&$temporaryPath) {
                $temporaryPath = $path;
                $this->assertSame($this->audio(), file_get_contents($path));
                throw new \RuntimeException('Processing failed');
            });
            $this->fail('Expected processing exception.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Processing failed', $e->getMessage());
        }
        $this->assertFileDoesNotExist($temporaryPath);
    }

    public function test_local_generation_and_command_failure_reporting(): void
    {
        Storage::fake('public');
        config(['filesystems.default' => 'public']);
        $track = $this->track();
        $this->artisan('relay:generate-waveforms')->assertFailed();
        Storage::disk('public')->put($track->storage_path, $this->audio());
        $this->artisan('relay:generate-waveforms')->expectsOutput('Generated 1 waveform(s).')->assertSuccessful();
        Storage::disk('public')->assertExists($track->fresh()->waveform_image_path);
        $this->artisan('relay:generate-waveforms')->expectsOutput('No tracks need waveform generation.')->assertSuccessful();
    }
}
