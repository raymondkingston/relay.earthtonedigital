<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Jobs\GenerateTrackWaveform;
use App\Models\Track;

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

    foreach ($tracks as $track) {
        GenerateTrackWaveform::dispatchSync($track);
        $bar->advance();
    }

    $bar->finish();
    $this->newLine(2);
    $this->info("Processed {$tracks->count()} tracks.");

    return 0;
})->purpose('Generate missing track waveform images');
