<?php

namespace Tests\Feature;

use App\Livewire\ProjectSidebar;
use App\Models\Call;
use App\Models\Project;
use App\Models\Setting;
use App\Models\Snapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LivewireProjectSidebarTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;
    protected string $testPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->testPath = sys_get_temp_dir().'/test-project-'.uniqid();
        mkdir($this->testPath, 0755, true);

        $this->project = Project::factory()->create([
            'name' => 'Test Project',
            'full_path' => $this->testPath,
        ]);

        Setting::setSetting('input_cost', 3.0);
        Setting::setSetting('output_cost', 15.0);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testPath)) {
            shell_exec("rm -rf {$this->testPath}");
        }

        parent::tearDown();
    }

    public function test_component_can_mount()
    {
        Livewire::test(ProjectSidebar::class, ['project' => $this->project])
            ->assertStatus(200);
    }

    public function test_component_loads_project_data()
    {
        Livewire::test(ProjectSidebar::class, ['project' => $this->project])
            ->assertSet('project.id', $this->project->id)
            ->assertSet('currentPath', $this->testPath);
    }

    public function test_count_tokens_calculates_correctly()
    {
        Call::factory()->create([
            'project_id' => $this->project->id,
            'prompt_tokens' => 1000,
            'completion_tokens' => 500,
        ]);

        Call::factory()->create([
            'project_id' => $this->project->id,
            'prompt_tokens' => 2000,
            'completion_tokens' => 1000,
        ]);

        Livewire::test(ProjectSidebar::class, ['project' => $this->project])
            ->assertSet('inputTokens', 3000)
            ->assertSet('outputTokens', 1500);
    }

    public function test_count_tokens_calculates_costs()
    {
        Call::factory()->create([
            'project_id' => $this->project->id,
            'prompt_tokens' => 1_000_000,
            'completion_tokens' => 1_000_000,
        ]);

        Livewire::test(ProjectSidebar::class, ['project' => $this->project])
            ->call('countTokens')
            ->assertSet('inputCost', 3.0)  // 1M tokens * 3.0 / 1M
            ->assertSet('outputCost', 15.0); // 1M tokens * 15.0 / 1M
    }

    public function test_loads_snapshots_on_mount()
    {
        Snapshot::factory()->count(3)->create([
            'project_id' => $this->project->id,
        ]);

        Livewire::test(ProjectSidebar::class, ['project' => $this->project])
            ->assertSet('snapshots', function ($snapshots) {
                return count($snapshots) === 3;
            });
    }

    public function test_snapshots_are_ordered_by_created_at_desc()
    {
        $snapshot1 = Snapshot::factory()->create([
            'project_id' => $this->project->id,
            'created_at' => now()->subDays(2),
        ]);

        $snapshot2 = Snapshot::factory()->create([
            'project_id' => $this->project->id,
            'created_at' => now()->subDay(),
        ]);

        $snapshot3 = Snapshot::factory()->create([
            'project_id' => $this->project->id,
            'created_at' => now(),
        ]);

        Livewire::test(ProjectSidebar::class, ['project' => $this->project])
            ->assertSet('snapshots', function ($snapshots) use ($snapshot3, $snapshot2, $snapshot1) {
                return $snapshots[0]->id === $snapshot3->id
                    && $snapshots[1]->id === $snapshot2->id
                    && $snapshots[2]->id === $snapshot1->id;
            });
    }

    public function test_add_file_adds_to_project_files()
    {
        $filePath = $this->testPath.'/test.txt';
        file_put_contents($filePath, 'test content');

        Livewire::test(ProjectSidebar::class, ['project' => $this->project])
            ->call('addFile', $filePath);

        $this->project->refresh();
        $this->assertContains($filePath, $this->project->files);
    }

    public function test_add_file_does_not_duplicate()
    {
        $filePath = $this->testPath.'/test.txt';
        file_put_contents($filePath, 'test content');

        Livewire::test(ProjectSidebar::class, ['project' => $this->project])
            ->call('addFile', $filePath)
            ->call('addFile', $filePath);

        $this->project->refresh();
        $this->assertCount(1, $this->project->files);
    }

    public function test_remove_file_removes_from_project_files()
    {
        $filePath = $this->testPath.'/test.txt';
        file_put_contents($filePath, 'test content');

        $this->project->files = [$filePath];
        $this->project->save();

        Livewire::test(ProjectSidebar::class, ['project' => $this->project])
            ->call('removeFile', 0);

        $this->project->refresh();
        $this->assertEmpty($this->project->files);
    }

    public function test_refresh_files_removes_non_existent_files()
    {
        $existingFile = $this->testPath.'/existing.txt';
        $nonExistentFile = $this->testPath.'/nonexistent.txt';

        file_put_contents($existingFile, 'content');

        $this->project->files = [$existingFile, $nonExistentFile];
        $this->project->save();

        Livewire::test(ProjectSidebar::class, ['project' => $this->project])
            ->call('refreshFiles');

        $this->project->refresh();
        $this->assertCount(1, $this->project->files);
        $this->assertContains($existingFile, $this->project->files);
    }

    public function test_refresh_files_populates_file_metadata()
    {
        $filePath = $this->testPath.'/test.txt';
        file_put_contents($filePath, 'test content');

        $this->project->files = [$filePath];
        $this->project->save();

        Livewire::test(ProjectSidebar::class, ['project' => $this->project])
            ->assertSet('files', function ($files) {
                return count($files) === 1
                    && isset($files[0]['name'])
                    && isset($files[0]['full_path'])
                    && isset($files[0]['size'])
                    && isset($files[0]['mime_type']);
            });
    }

    public function test_create_snapshot_creates_git_commit()
    {
        // Initialize git repo
        shell_exec("cd {$this->testPath} && git init");
        shell_exec("cd {$this->testPath} && git config user.email 'test@test.com'");
        shell_exec("cd {$this->testPath} && git config user.name 'Test User'");
        file_put_contents($this->testPath.'/test.txt', 'content');
        shell_exec("cd {$this->testPath} && git add .");

        Livewire::test(ProjectSidebar::class, ['project' => $this->project])
            ->call('createSnapshot');

        $this->assertGreaterThan(0, $this->project->snapshots()->count());
    }

    public function test_create_snapshot_with_no_changes()
    {
        // Initialize git repo but make no changes
        shell_exec("cd {$this->testPath} && git init");
        shell_exec("cd {$this->testPath} && git config user.email 'test@test.com'");
        shell_exec("cd {$this->testPath} && git config user.name 'Test User'");

        Livewire::test(ProjectSidebar::class, ['project' => $this->project])
            ->call('createSnapshot')
            ->assertSessionHas('snapshot', 'Failed to create snapshot. No changes?');
    }

    public function test_restore_snapshot_redirects()
    {
        $snapshot = Snapshot::factory()->create([
            'project_id' => $this->project->id,
            'commit' => 'abc123',
        ]);

        Livewire::test(ProjectSidebar::class, ['project' => $this->project])
            ->call('restoreSnapshot', $snapshot->id)
            ->assertRedirect(route('projects.restore', [
                'project' => $this->project,
                'snapshotId' => $snapshot->id,
            ]));
    }

    public function test_browse_files_lists_directory_contents()
    {
        file_put_contents($this->testPath.'/file1.txt', 'content');
        file_put_contents($this->testPath.'/file2.txt', 'content');
        mkdir($this->testPath.'/subfolder');

        Livewire::test(ProjectSidebar::class, ['project' => $this->project])
            ->call('browseFiles')
            ->assertSet('directoryContents', function ($contents) {
                return count($contents) === 3;
            });
    }

    public function test_browse_files_excludes_hidden_files()
    {
        file_put_contents($this->testPath.'/visible.txt', 'content');
        file_put_contents($this->testPath.'/.hidden', 'content');

        Livewire::test(ProjectSidebar::class, ['project' => $this->project])
            ->call('browseFiles')
            ->assertSet('directoryContents', function ($contents) {
                return count($contents) === 1 && $contents[0]['name'] === 'visible.txt';
            });
    }

    public function test_browse_files_sanitizes_path()
    {
        // Try to browse outside project directory
        Livewire::test(ProjectSidebar::class, ['project' => $this->project])
            ->call('browseFiles', '/tmp')
            ->assertSet('currentPath', $this->testPath);
    }

    public function test_browse_files_handles_subdirectories()
    {
        $subdir = $this->testPath.'/subfolder';
        mkdir($subdir);
        file_put_contents($subdir.'/file.txt', 'content');

        Livewire::test(ProjectSidebar::class, ['project' => $this->project])
            ->call('browseFiles', $subdir)
            ->assertSet('currentPath', $subdir)
            ->assertSet('directoryContents', function ($contents) {
                return count($contents) === 1 && $contents[0]['name'] === 'file.txt';
            });
    }

    public function test_directory_contents_includes_file_metadata()
    {
        file_put_contents($this->testPath.'/test.txt', 'content');
        mkdir($this->testPath.'/folder');

        Livewire::test(ProjectSidebar::class, ['project' => $this->project])
            ->call('browseFiles')
            ->assertSet('directoryContents', function ($contents) {
                foreach ($contents as $item) {
                    if (!isset($item['name']) || !isset($item['path']) || !isset($item['type'])) {
                        return false;
                    }
                }
                return true;
            });
    }

    public function test_input_price_per_m_loaded_from_settings()
    {
        Livewire::test(ProjectSidebar::class, ['project' => $this->project])
            ->assertSet('inputPricePerM', 3.0);
    }

    public function test_output_price_per_m_loaded_from_settings()
    {
        Livewire::test(ProjectSidebar::class, ['project' => $this->project])
            ->assertSet('outputPricePerM', 15.0);
    }

    public function test_delete_project_redirects()
    {
        Livewire::test(ProjectSidebar::class, ['project' => $this->project])
            ->call('deleteProject')
            ->assertRedirect(route('projects.delete', $this->project));
    }
}
