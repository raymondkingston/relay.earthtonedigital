<?php

use App\Jobs\GenerateTrackWaveform;
use App\Models\Track;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('relay:generate-waveforms {--force : Regenerate existing waveform images}', function () {
    $tracks = Track::query()
        ->when(! $this->option('force'), fn ($query) => $query->whereNull('waveform_image_path'))
        ->whereNotNull('storage_path')
        ->orderBy('id')
        ->get();

    if ($tracks->isEmpty()) {
        $this->info('No tracks need waveform generation.');

        return 0;
    }

    $bar = $this->output->createProgressBar($tracks->count());
    $bar->start();

    $failed = [];
    foreach ($tracks as $track) {
        if (! (new GenerateTrackWaveform($track))->handle()) {
            $failed[] = $track->id;
        }
        $bar->advance();
    }

    $bar->finish();
    $this->newLine(2);
    $succeeded = $tracks->count() - count($failed);
    $this->info("Generated {$succeeded} waveform(s).");
    if ($failed) {
        $this->error('Failed track IDs: '.implode(', ', $failed).'. See storage/logs/laravel.log for details.');
    }

    return $failed ? 1 : 0;
})->purpose('Generate missing track waveform images');
