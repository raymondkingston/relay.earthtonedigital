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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();

            $table->foreignId('artist_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('title');
            $table->string('slug')->unique();
            
            $table->text('description')->nullable();

            $table->string('type')->nullable(); // show, rehearsal, session, etc.
            $table->date('recorded_at')->nullable();

            $table->string('venue')->nullable();
            $table->string('city')->nullable();

            $table->string('cover_art_path')->nullable();

            $table->string('visibility')->default('public'); // private | unlisted | public

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
