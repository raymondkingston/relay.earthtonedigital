<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tracks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('title');
            $table->text('notes')->nullable();

            $table->unsignedSmallInteger('track_number')->nullable();

            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedBigInteger('file_size_bytes')->nullable();

            $table->string('storage_path');       // S3 key or local path
            $table->string('original_filename')->nullable();  // what the user uploaded
            $table->string('format')->nullable(); // mp3, wav, flac, etc.
            $table->unsignedInteger('bitrate')->nullable(); // e.g. 320000

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracks');
    }
};
