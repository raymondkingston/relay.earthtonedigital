<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisibilityAccessTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\DataProvider('restrictedProjectRequests')]
    public function test_guests_see_visibility_message_without_valid_share_key(string $visibility, array $query): void
    {
        $project = $this->createProject('Restricted Project', $visibility);

        $this->get(route('projects.show', ['project' => $project, ...$query]))
            ->assertForbidden()
            ->assertSee("That project is marked as {$visibility}.")
            ->assertSee('Be sure to use the exact link you were given to see non-public projects, or contact Ray directly.')
            ->assertDontSee($project->title)
            ->assertDontSee($project->share_token)
            ->assertDontSee($project->artist->share_token);
    }

    public static function restrictedProjectRequests(): array
    {
        return [
            ['private', []],
            ['unlisted', []],
            ['private', ['project_key' => 'incorrect']],
            ['unlisted', ['artist_key' => 'incorrect']],
        ];
    }

    public function test_missing_project_still_returns_not_found(): void
    {
        $this->get('/projects/does-not-exist')->assertNotFound();
    }

    public function test_project_share_key_allows_guest_to_view_private_project(): void
    {
        $project = $this->createProject('Private Project', 'private');

        $this->get(route('projects.show', [
            'project' => $project,
            'project_key' => $project->share_token,
        ]))
            ->assertOk()
            ->assertSee('Private Project');
    }

    public function test_artist_share_key_reveals_artist_private_projects(): void
    {
        $artist = Artist::create([
            'name' => 'Shared Artist',
            'slug' => 'shared-artist',
        ]);

        Project::create([
            'artist_id' => $artist->id,
            'title' => 'Private Project',
            'slug' => 'private-project',
            'visibility' => 'private',
        ]);

        $this->get(route('artists.show', $artist))
            ->assertOk()
            ->assertDontSee('Private Project');

        $this->get(route('artists.show', [
            'artist' => $artist,
            'artist_key' => $artist->share_token,
        ]))
            ->assertOk()
            ->assertSee('Private Project');
    }

    public function test_authenticated_users_can_view_private_projects(): void
    {
        $project = $this->createProject('Private Project', 'private');

        $this->actingAs(User::factory()->create())
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Private Project');
    }

    protected function createProject(string $title, string $visibility): Project
    {
        $artist = Artist::create([
            'name' => 'Test Artist',
            'slug' => 'test-artist',
        ]);

        return Project::create([
            'artist_id' => $artist->id,
            'title' => $title,
            'slug' => str($title)->slug()->toString(),
            'visibility' => $visibility,
        ]);
    }
}
