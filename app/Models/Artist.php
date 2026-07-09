<?php

namespace App\Models;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Artist extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'share_token',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function hasValidShareKey(Request $request): bool
    {
        $key = $request->query('artist_key');

        return filled($this->share_token)
            && is_string($key)
            && hash_equals($this->share_token, $key);
    }

    public function shareParameters(): array
    {
        return filled($this->share_token)
            ? ['artist_key' => $this->share_token]
            : [];
    }

    protected static function booted(): void
    {
        static::creating(function (Artist $artist) {
            $artist->share_token ??= static::uniqueShareToken();
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
