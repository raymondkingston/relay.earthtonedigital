<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

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

    protected static function booted(): void
    {
        static::creating(function (Project $project) {
            if (! $project->cover_art_path) {

                $fpoOptions = config('fpo-images');

                $project->cover_art_path = Arr::random($fpoOptions);
            }
        });
    }
}
