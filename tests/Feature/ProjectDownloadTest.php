<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Project;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use ZipArchive;

class ProjectDownloadTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('downloadAccess')]
    public function test_download_access_and_archive_contents(string $visibility, string $access, bool $allowed): void
    {
        Storage::fake('public');
        config(['filesystems.default' => 'public']);
        $artist = Artist::create(['name' => 'Artist', 'slug' => 'artist']);
        $project = Project::create([
            'artist_id' => $artist->id, 'title' => 'Test Project',
            'slug' => 'test-project', 'visibility' => $visibility,
        ]);
        foreach ([2, 1] as $number) {
            Storage::disk('public')->put("audio/{$number}.mp3", "audio {$number}");
            Track::withoutEvents(fn () => Track::create([
                'project_id' => $project->id, 'title' => '../Same title',
                'track_number' => $number, 'storage_path' => "audio/{$number}.mp3",
            ]));
        }
        $parameters = ['project' => $project];
        if ($access === 'project') {
            $parameters['project_key'] = $project->share_token;
        } elseif ($access === 'artist') {
            $parameters['artist_key'] = $artist->share_token;
        } elseif ($access === 'invalid') {
            $parameters['project_key'] = 'incorrect';
        } elseif ($access === 'user') {
            $this->actingAs(User::factory()->create());
        }

        $response = $this->get(route('projects.download', $parameters));
        if (! $allowed) {
            $response->assertForbidden()->assertSee("marked as {$visibility}");

            return;
        }

        $response->assertDownload('test-project.zip')->assertHeader('Content-Type', 'application/zip');
        $path = $response->baseResponse->getFile()->getPathname();
        try {
            $zip = new ZipArchive;
            $this->assertTrue($zip->open($path));
            $this->assertSame(2, $zip->numFiles);
            $this->assertSame('audio 1', $zip->getFromName('01-same-title.mp3'));
            $this->assertSame('audio 2', $zip->getFromName('02-same-title.mp3'));
            $zip->close();
        } finally {
            unlink($path);
        }

        $this->get(route('projects.show', $parameters))->assertOk()
            ->assertSee('Download All')
            ->assertSee(route('projects.download', $parameters));
    }

    public static function downloadAccess(): array
    {
        return [
            ['public', 'guest', true],
            ['private', 'guest', false],
            ['unlisted', 'guest', false],
            ['private', 'invalid', false],
            ['unlisted', 'invalid', false],
            ['private', 'project', true],
            ['unlisted', 'project', true],
            ['private', 'artist', true],
            ['private', 'user', true],
        ];
    }

    public function test_empty_projects_have_no_download(): void
    {
        $artist = Artist::create(['name' => 'Artist', 'slug' => 'artist']);
        $project = Project::create([
            'artist_id' => $artist->id, 'title' => 'Empty',
            'slug' => 'empty', 'visibility' => 'public',
        ]);

        $this->get(route('projects.show', $project))->assertOk()->assertDontSee('Download All');
        $this->get(route('projects.download', $project))->assertNotFound();
    }
}
