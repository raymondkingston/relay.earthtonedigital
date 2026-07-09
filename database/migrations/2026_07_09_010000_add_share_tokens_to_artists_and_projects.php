<?php

use App\Models\Artist;
use App\Models\Project;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artists', function (Blueprint $table) {
            $table->string('share_token', 16)->nullable()->unique()->after('slug');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->string('share_token', 16)->nullable()->unique()->after('slug');
        });

        Artist::query()
            ->whereNull('share_token')
            ->get()
            ->each(fn (Artist $artist) => $artist->forceFill([
                'share_token' => $this->uniqueToken(Artist::class),
            ])->save());

        Project::query()
            ->whereNull('share_token')
            ->get()
            ->each(fn (Project $project) => $project->forceFill([
                'share_token' => $this->uniqueToken(Project::class),
            ])->save());
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropUnique(['share_token']);
            $table->dropColumn('share_token');
        });

        Schema::table('artists', function (Blueprint $table) {
            $table->dropUnique(['share_token']);
            $table->dropColumn('share_token');
        });
    }

    protected function uniqueToken(string $model): string
    {
        do {
            $token = Str::random(12);
        } while ($model::query()->where('share_token', $token)->exists());

        return $token;
    }
};
