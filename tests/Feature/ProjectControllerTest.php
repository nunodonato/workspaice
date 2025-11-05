<?php

namespace Tests\Feature;

use App\Models\Call;
use App\Models\Message;
use App\Models\Project;
use App\Models\Snapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create and authenticate a user
        $this->user = User::factory()->create();
    }

    public function test_index_displays_all_projects()
    {
        $this->actingAs($this->user);

        $projects = Project::factory()->count(3)->create();

        $response = $this->get(route('projects'));

        $response->assertStatus(200);
        $response->assertViewIs('projects.index');
        $response->assertViewHas('projects');

        foreach ($projects as $project) {
            $response->assertSee($project->name);
        }
    }

    public function test_index_requires_authentication()
    {
        $response = $this->get(route('projects'));

        $response->assertRedirect(route('login'));
    }

    public function test_create_displays_project_creation_form()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('projects.create'));

        $response->assertStatus(200);
        $response->assertViewIs('projects.create');
    }

    public function test_show_displays_project_details()
    {
        $this->actingAs($this->user);

        $project = Project::factory()->create([
            'name' => 'Test Project',
            'description' => 'Test description',
        ]);

        $response = $this->get(route('projects.show', $project));

        $response->assertStatus(200);
        $response->assertViewIs('projects.show');
        $response->assertViewHas('project');
        $response->assertSee('Test Project');
    }

    public function test_edit_displays_project_edit_form()
    {
        $this->actingAs($this->user);

        $project = Project::factory()->create();

        $response = $this->get(route('projects.edit', $project));

        $response->assertStatus(200);
        $response->assertViewIs('projects.edit');
        $response->assertViewHas('project');
    }

    public function test_update_modifies_project()
    {
        $this->actingAs($this->user);

        $project = Project::factory()->create([
            'name' => 'Old Name',
            'description' => 'Old Description',
        ]);

        $response = $this->put(route('projects.update', $project), [
            'name' => 'New Name',
            'description' => 'New Description',
        ]);

        $response->assertRedirect(route('projects.show', $project));
        $response->assertSessionHas('success', 'Project updated successfully.');

        $project->refresh();
        $this->assertEquals('New Name', $project->name);
        $this->assertEquals('New Description', $project->description);
    }

    public function test_update_validates_required_fields()
    {
        $this->actingAs($this->user);

        $project = Project::factory()->create();

        $response = $this->put(route('projects.update', $project), [
            'name' => '',
            'description' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'description']);
    }

    public function test_update_validates_name_length()
    {
        $this->actingAs($this->user);

        $project = Project::factory()->create();

        $response = $this->put(route('projects.update', $project), [
            'name' => str_repeat('a', 256), // Exceeds 255 character limit
            'description' => 'Valid description',
        ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_destroy_deletes_project_and_related_data()
    {
        $this->actingAs($this->user);

        $testPath = sys_get_temp_dir().'/test-project-'.uniqid();
        mkdir($testPath, 0755, true);

        $project = Project::factory()->create([
            'full_path' => $testPath,
        ]);

        // Create related data
        Message::factory()->count(3)->create(['project_id' => $project->id]);
        Call::factory()->count(2)->create(['project_id' => $project->id]);

        $response = $this->delete(route('projects.destroy', $project));

        $response->assertRedirect(route('projects'));
        $response->assertSessionHas('success', 'Project deleted successfully.');

        // Verify project is deleted
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);

        // Verify related messages are deleted
        $this->assertEquals(0, Message::where('project_id', $project->id)->count());

        // Verify related calls are deleted
        $this->assertEquals(0, Call::where('project_id', $project->id)->count());

        // Verify directory is deleted
        $this->assertDirectoryDoesNotExist($testPath);
    }

    public function test_chat_route_displays_project()
    {
        $this->actingAs($this->user);

        $project = Project::factory()->create();

        $response = $this->get(route('projects.chat', $project));

        $response->assertStatus(200);
        $response->assertViewIs('projects.show');
        $response->assertViewHas('project');
    }

    public function test_restore_snapshot_reverts_project_state()
    {
        $this->actingAs($this->user);

        $testPath = sys_get_temp_dir().'/test-project-'.uniqid();
        mkdir($testPath, 0755, true);

        // Initialize git repo
        shell_exec("cd $testPath && git init && git config user.email 'test@test.com' && git config user.name 'Test'");
        file_put_contents($testPath.'/test.txt', 'initial content');
        shell_exec("cd $testPath && git add . && git commit -m 'Initial commit'");
        $initialCommit = trim(shell_exec("cd $testPath && git rev-parse HEAD"));

        $project = Project::factory()->create([
            'full_path' => $testPath,
        ]);

        // Create snapshot
        $snapshot = Snapshot::factory()->create([
            'project_id' => $project->id,
            'commit' => $initialCommit,
        ]);

        // Create messages after snapshot
        Message::factory()->create([
            'project_id' => $project->id,
            'created_at' => now()->addMinute(),
        ]);

        // Make a new commit
        file_put_contents($testPath.'/test.txt', 'modified content');
        shell_exec("cd $testPath && git add . && git commit -m 'Modified'");

        $response = $this->post(route('projects.restore-snapshot', [$project, $snapshot]));

        $response->assertRedirect(route('projects.show', $project));
        $response->assertSessionHas('snapshot', 'Project restored to snapshot');

        // Verify file was restored
        $this->assertEquals('initial content', file_get_contents($testPath.'/test.txt'));

        // Clean up
        shell_exec("rm -rf $testPath");
    }

    public function test_restore_snapshot_returns_404_for_invalid_snapshot()
    {
        $this->actingAs($this->user);

        $project = Project::factory()->create();

        $response = $this->post(route('projects.restore-snapshot', [$project, 99999]));

        $response->assertStatus(404);
    }

    public function test_restore_snapshot_deletes_messages_after_snapshot()
    {
        $this->actingAs($this->user);

        $testPath = sys_get_temp_dir().'/test-project-'.uniqid();
        mkdir($testPath, 0755, true);

        // Initialize git repo
        shell_exec("cd $testPath && git init && git config user.email 'test@test.com' && git config user.name 'Test'");
        file_put_contents($testPath.'/test.txt', 'content');
        shell_exec("cd $testPath && git add . && git commit -m 'Initial'");
        $commit = trim(shell_exec("cd $testPath && git rev-parse HEAD"));

        $project = Project::factory()->create([
            'full_path' => $testPath,
        ]);

        $snapshotTime = now();
        $snapshot = Snapshot::factory()->create([
            'project_id' => $project->id,
            'commit' => $commit,
            'created_at' => $snapshotTime,
        ]);

        // Create messages before and after snapshot
        $messageBefore = Message::factory()->create([
            'project_id' => $project->id,
            'created_at' => $snapshotTime->copy()->subMinute(),
        ]);

        $messageAfter = Message::factory()->create([
            'project_id' => $project->id,
            'created_at' => $snapshotTime->copy()->addMinute(),
        ]);

        $this->post(route('projects.restore-snapshot', [$project, $snapshot]));

        // Message before should still exist
        $this->assertDatabaseHas('messages', ['id' => $messageBefore->id]);

        // Message after should be deleted
        $this->assertDatabaseMissing('messages', ['id' => $messageAfter->id]);

        // Clean up
        shell_exec("rm -rf $testPath");
    }

    public function test_unauthorized_user_cannot_access_projects()
    {
        $project = Project::factory()->create();

        // Test various routes without authentication
        $this->get(route('projects'))->assertRedirect(route('login'));
        $this->get(route('projects.show', $project))->assertRedirect(route('login'));
        $this->get(route('projects.edit', $project))->assertRedirect(route('login'));
        $this->get(route('projects.create'))->assertRedirect(route('login'));
    }
}
