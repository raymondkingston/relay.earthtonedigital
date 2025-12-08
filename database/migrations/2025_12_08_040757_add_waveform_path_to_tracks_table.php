<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tracks', function (Blueprint $table) {
            // Path to a generated waveform image (e.g. PNG) on your default disk
            $table->string('waveform_image_path')->nullable()->after('storage_path');

            // Optional: JSON-encoded waveform samples if you decide to store data instead of / in addition to image
            $table->json('waveform_data')->nullable()->after('waveform_image_path');

            // Optional: timestamp for bookkeeping / cache-busting / re-gen logic
            $table->timestamp('waveform_generated_at')->nullable()->after('waveform_data');
        });
    }

    public function down(): void
    {
        Schema::table('tracks', function (Blueprint $table) {
            $table->dropColumn([
                'waveform_image_path',
                'waveform_data',
                'waveform_generated_at',
            ]);
        });
    }
};
