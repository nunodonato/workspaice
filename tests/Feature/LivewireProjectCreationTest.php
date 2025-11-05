<?php

namespace Tests\Feature;

use App\Jobs\SendMessageJob;
use App\Livewire\ProjectCreation;
use App\Models\Project;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class LivewireProjectCreationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        Setting::setSetting('system_info', 'Ubuntu 22.04, PHP 8.2');
    }

    protected function tearDown(): void
    {
        // Clean up any created project directories
        $projects = Project::all();
        foreach ($projects as $project) {
            if (file_exists($project->full_path)) {
                shell_exec("rm -rf {$project->full_path}");
            }
        }

        parent::tearDown();
    }

    public function test_component_can_mount()
    {
        Livewire::test(ProjectCreation::class)
            ->assertStatus(200);
    }

    public function test_system_info_is_loaded_from_settings()
    {
        Livewire::test(ProjectCreation::class)
            ->assertSet('system', 'Ubuntu 22.04, PHP 8.2');
    }

    public function test_validation_requires_name()
    {
        Livewire::test(ProjectCreation::class)
            ->set('name', '')
            ->set('description', 'A valid description that is long enough')
            ->set('specs', 'Valid specs here')
            ->set('system', 'Valid system')
            ->call('createProject')
            ->assertHasErrors(['name']);
    }

    public function test_validation_requires_name_min_length()
    {
        Livewire::test(ProjectCreation::class)
            ->set('name', 'ab')
            ->set('description', 'A valid description that is long enough')
            ->set('specs', 'Valid specs here')
            ->set('system', 'Valid system')
            ->call('createProject')
            ->assertHasErrors(['name']);
    }

    public function test_validation_requires_name_max_length()
    {
        Livewire::test(ProjectCreation::class)
            ->set('name', str_repeat('a', 256))
            ->set('description', 'A valid description that is long enough')
            ->set('specs', 'Valid specs here')
            ->set('system', 'Valid system')
            ->call('createProject')
            ->assertHasErrors(['name']);
    }

    public function test_validation_requires_description()
    {
        Livewire::test(ProjectCreation::class)
            ->set('name', 'Test Project')
            ->set('description', '')
            ->set('specs', 'Valid specs here')
            ->set('system', 'Valid system')
            ->call('createProject')
            ->assertHasErrors(['description']);
    }

    public function test_validation_requires_description_min_length()
    {
        Livewire::test(ProjectCreation::class)
            ->set('name', 'Test Project')
            ->set('description', 'Too short')
            ->set('specs', 'Valid specs here')
            ->set('system', 'Valid system')
            ->call('createProject')
            ->assertHasErrors(['description']);
    }

    public function test_validation_requires_specs()
    {
        Livewire::test(ProjectCreation::class)
            ->set('name', 'Test Project')
            ->set('description', 'A valid description that is long enough')
            ->set('specs', '')
            ->set('system', 'Valid system')
            ->call('createProject')
            ->assertHasErrors(['specs']);
    }

    public function test_validation_requires_system()
    {
        Livewire::test(ProjectCreation::class)
            ->set('name', 'Test Project')
            ->set('description', 'A valid description that is long enough')
            ->set('specs', 'Valid specs here')
            ->set('system', '')
            ->call('createProject')
            ->assertHasErrors(['system']);
    }

    public function test_real_time_validation_on_property_update()
    {
        Livewire::test(ProjectCreation::class)
            ->set('name', 'ab')
            ->assertHasErrors(['name'])
            ->set('name', 'Valid Name')
            ->assertHasNoErrors(['name']);
    }

    public function test_create_project_with_valid_data()
    {
        Livewire::test(ProjectCreation::class)
            ->set('name', 'My Test Project')
            ->set('description', 'This is a test project with a valid description')
            ->set('specs', 'Laravel 11, PHP 8.2, MySQL')
            ->set('system', 'Ubuntu 22.04')
            ->set('autostart', false)
            ->call('createProject')
            ->assertRedirect();

        $this->assertDatabaseHas('projects', [
            'name' => 'My Test Project',
            'description' => 'This is a test project with a valid description',
            'technical_specs' => 'Laravel 11, PHP 8.2, MySQL',
            'system_description' => 'Ubuntu 22.04',
        ]);
    }

    public function test_create_project_creates_directory()
    {
        Livewire::test(ProjectCreation::class)
            ->set('name', 'Directory Test')
            ->set('description', 'Testing directory creation functionality')
            ->set('specs', 'Test specs')
            ->set('system', 'Test system')
            ->set('autostart', false)
            ->call('createProject');

        $project = Project::where('name', 'Directory Test')->first();

        $this->assertNotNull($project);
        $this->assertDirectoryExists($project->full_path);
        $this->assertDirectoryExists($project->full_path.'/.workspaice');
    }

    public function test_create_project_initializes_git()
    {
        Livewire::test(ProjectCreation::class)
            ->set('name', 'Git Test')
            ->set('description', 'Testing git initialization')
            ->set('specs', 'Test specs')
            ->set('system', 'Test system')
            ->set('autostart', false)
            ->call('createProject');

        $project = Project::where('name', 'Git Test')->first();

        $this->assertDirectoryExists($project->full_path.'/.git');
    }

    public function test_create_project_generates_slug()
    {
        Livewire::test(ProjectCreation::class)
            ->set('name', 'My Test Project')
            ->set('description', 'Testing slug generation')
            ->set('specs', 'Test specs')
            ->set('system', 'Test system')
            ->set('autostart', false)
            ->call('createProject');

        $project = Project::where('name', 'My Test Project')->first();

        $this->assertEquals('my-test-project', $project->slug);
    }

    public function test_create_project_without_autostart()
    {
        Queue::fake();

        Livewire::test(ProjectCreation::class)
            ->set('name', 'No Autostart')
            ->set('description', 'Testing without autostart')
            ->set('specs', 'Test specs')
            ->set('system', 'Test system')
            ->set('autostart', false)
            ->call('createProject');

        Queue::assertNothingPushed();

        $project = Project::where('name', 'No Autostart')->first();
        $this->assertStringContainsString('Discuss the project with the user', $project->tasks);
    }

    public function test_create_project_with_autostart()
    {
        Queue::fake();

        Livewire::test(ProjectCreation::class)
            ->set('name', 'With Autostart')
            ->set('description', 'Testing with autostart enabled')
            ->set('specs', 'Test specs')
            ->set('system', 'Test system')
            ->set('autostart', true)
            ->call('createProject');

        Queue::assertPushed(SendMessageJob::class, function ($job) {
            return $job->project->name === 'With Autostart';
        });

        $project = Project::where('name', 'With Autostart')->first();
        $this->assertStringContainsString('Build a plan of action', $project->tasks);
    }

    public function test_create_project_sets_proper_tasks_for_autostart()
    {
        Queue::fake();

        Livewire::test(ProjectCreation::class)
            ->set('name', 'Autostart Tasks')
            ->set('description', 'Testing autostart task creation')
            ->set('specs', 'Test specs')
            ->set('system', 'Test system')
            ->set('autostart', true)
            ->call('createProject');

        $project = Project::where('name', 'Autostart Tasks')->first();

        $this->assertStringContainsString('✅ Scan the project directory', $project->tasks);
        $this->assertStringContainsString('➡️ Ask clarifying questions', $project->tasks);
        $this->assertStringContainsString('⬜ Consider the challenges', $project->tasks);
        $this->assertStringContainsString('⬜ Build a plan of action', $project->tasks);
    }

    public function test_create_project_shows_success_message()
    {
        Livewire::test(ProjectCreation::class)
            ->set('name', 'Success Test')
            ->set('description', 'Testing success message')
            ->set('specs', 'Test specs')
            ->set('system', 'Test system')
            ->set('autostart', false)
            ->call('createProject')
            ->assertSessionHas('message', 'Project created successfully.');
    }

    public function test_create_project_redirects_to_project_show()
    {
        Livewire::test(ProjectCreation::class)
            ->set('name', 'Redirect Test')
            ->set('description', 'Testing redirect after creation')
            ->set('specs', 'Test specs')
            ->set('system', 'Test system')
            ->set('autostart', false)
            ->call('createProject')
            ->assertRedirect();
    }

    public function test_project_path_uses_home_directory()
    {
        Livewire::test(ProjectCreation::class)
            ->set('name', 'Path Test')
            ->set('description', 'Testing path generation')
            ->set('specs', 'Test specs')
            ->set('system', 'Test system')
            ->set('autostart', false)
            ->call('createProject');

        $project = Project::where('name', 'Path Test')->first();
        $homeDir = getenv('HOME');

        $this->assertStringContainsString($homeDir.'/projects/', $project->full_path);
    }

    public function test_creates_workspaice_hidden_directory()
    {
        Livewire::test(ProjectCreation::class)
            ->set('name', 'Hidden Dir Test')
            ->set('description', 'Testing hidden directory creation')
            ->set('specs', 'Test specs')
            ->set('system', 'Test system')
            ->set('autostart', false)
            ->call('createProject');

        $project = Project::where('name', 'Hidden Dir Test')->first();

        $this->assertDirectoryExists($project->full_path.'/.workspaice');
    }
}
