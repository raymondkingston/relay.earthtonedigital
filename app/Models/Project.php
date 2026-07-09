<?php

namespace App\Models;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Project extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'artist_id',
        'title',
        'slug',
        'share_token',
        'description',
        'type',
        'recorded_at',
        'venue',
        'city',
        'cover_art_path',
        'visibility',
    ];

    protected $casts = [
        'recorded_at' => 'date', // Cast recorded_at to a date object
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }

    public function tracks()
    {
        return $this->hasMany(Track::class);
    }

    public function isPublic(): bool
    {
        return $this->visibility === 'public';
    }

    public function hasValidShareKey(Request $request): bool
    {
        $key = $request->query('project_key');

        return filled($this->share_token)
            && is_string($key)
            && hash_equals($this->share_token, $key);
    }

    public function isVisibleTo(Request $request): bool
    {
        return $request->user()
            || $this->isPublic()
            || $this->hasValidShareKey($request)
            || $this->artist?->hasValidShareKey($request);
    }

    public function shareParameters(): array
    {
        return array_filter([
            'artist_key' => $this->artist?->share_token,
            'project_key' => $this->share_token,
        ]);
    }

    protected static function booted(): void
    {
        static::creating(function (Project $project) {
            $project->share_token ??= static::uniqueShareToken();

            if (! $project->cover_art_path) {

                $fpoOptions = config('fpo-images');

                $project->cover_art_path = Arr::random($fpoOptions);
            }
        });
    }

    protected static function uniqueShareToken(): string
    {
        do {
            $token = Str::random(12);
        } while (static::query()->where('share_token', $token)->exists());

        return $token;
    }
}
