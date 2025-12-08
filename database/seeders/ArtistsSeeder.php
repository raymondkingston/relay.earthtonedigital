<?php

namespace Database\Seeders;

use App\Models\Artist;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ArtistsSeeder extends Seeder
{
    public function run(): void
    {
        // Automatically create regular artists
        Artist::create([
            'name' => 'Ray Kingston',
            'slug' => 'ray-kingston',
        ]);

        Artist::create([
            'name' => 'Organized Dead',
            'slug' => 'organized-dead',
        ]);

        Artist::create([
            'name' => 'Surf Strider Band',
            'slug' => 'surf-strider-band',
        ]);

        Artist::create([
            'name' => 'Lincoln Street Groove Project',
            'slug' => 'lincoln-street-groove-project',
        ]);
 
        Artist::create([
            'name' => 'Open Mic',
            'slug' => 'open-mic',
        ]);
 
        // Artist::create([
        //     'name' => '',
        //     'slug' => '',
        // ]);
    }
}
