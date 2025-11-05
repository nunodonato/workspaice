<?php

namespace Tests\Unit;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AIFunctionsTest extends TestCase
{
    use RefreshDatabase;

    protected Project $project;
    protected string $testProjectPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a temporary test project directory
        $this->testProjectPath = sys_get_temp_dir().'/test-project-'.uniqid();
        mkdir($this->testProjectPath, 0755, true);

        // Create a test project
        $this->project = Project::factory()->create([
            'name' => 'Test Project',
            'full_path' => $this->testProjectPath,
            'description' => 'Test description',
            'technical_specs' => 'Test specs',
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up test directory
        if (file_exists($this->testProjectPath)) {
            $this->deleteDirectory($this->testProjectPath);
        }

        parent::tearDown();
    }

    private function deleteDirectory($dir)
    {
        if (!file_exists($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir.DIRECTORY_SEPARATOR.$file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function test_search_for_file_finds_matching_files()
    {
        // Create test files
        touch($this->testProjectPath.'/test.php');
        touch($this->testProjectPath.'/testing.php');
        touch($this->testProjectPath.'/other.txt');

        $result = searchForFile($this->project, 'test');

        $this->assertStringContainsString('test.php', $result);
        $this->assertStringContainsString('testing.php', $result);
        $this->assertStringNotContainsString('other.txt', $result);
    }

    public function test_search_for_file_case_insensitive()
    {
        touch($this->testProjectPath.'/TestFile.php');

        $result = searchForFile($this->project, 'testfile');

        $this->assertStringContainsString('TestFile.php', $result);
    }

    public function test_get_contents_from_file_returns_file_content()
    {
        $filePath = $this->testProjectPath.'/test.txt';
        file_put_contents($filePath, 'Hello World');

        $result = getContentsFromFile($this->project, $filePath);

        $this->assertEquals('Hello World', $result);
    }

    public function test_get_contents_from_file_returns_error_for_missing_file()
    {
        $filePath = $this->testProjectPath.'/nonexistent.txt';

        $result = getContentsFromFile($this->project, $filePath);

        $this->assertStringContainsString('Error: file does not exist', $result);
    }

    public function test_save_data_to_file_writes_content()
    {
        $filePath = $this->testProjectPath.'/output.txt';

        $result = saveDataToFile($this->project, $filePath, 'Test content', 'w');

        $this->assertEquals('Content saved.', $result);
        $this->assertTrue(file_exists($filePath));
        $this->assertEquals('Test content', file_get_contents($filePath));
    }

    public function test_save_data_to_file_appends_content()
    {
        $filePath = $this->testProjectPath.'/output.txt';
        file_put_contents($filePath, 'First line'.PHP_EOL);

        $result = saveDataToFile($this->project, $filePath, 'Second line', 'a');

        $this->assertEquals('Content saved.', $result);
        $this->assertStringContainsString('First line', file_get_contents($filePath));
        $this->assertStringContainsString('Second line', file_get_contents($filePath));
    }

    public function test_save_data_to_file_creates_directories()
    {
        $filePath = $this->testProjectPath.'/subdir/nested/file.txt';

        $result = saveDataToFile($this->project, $filePath, 'Content', 'w');

        $this->assertEquals('Content saved.', $result);
        $this->assertTrue(file_exists($filePath));
    }

    public function test_save_data_to_file_prevents_writing_outside_project()
    {
        $filePath = '/tmp/outside-project.txt';

        $result = saveDataToFile($this->project, $filePath, 'Content', 'w');

        $this->assertStringContainsString('Error: Invalid file path', $result);
        $this->assertFalse(file_exists($filePath));
    }

    public function test_save_data_to_file_returns_error_for_empty_content()
    {
        $filePath = $this->testProjectPath.'/empty.txt';

        $result = saveDataToFile($this->project, $filePath, '', 'w');

        $this->assertStringContainsString('Error:', $result);
    }

    public function test_save_data_to_file_returns_error_for_invalid_mode()
    {
        $filePath = $this->testProjectPath.'/test.txt';

        $result = saveDataToFile($this->project, $filePath, 'Content', 'x');

        $this->assertStringContainsString('Error: Invalid mode', $result);
    }

    public function test_get_files_in_folder_lists_contents()
    {
        // Create test structure
        touch($this->testProjectPath.'/file1.txt');
        touch($this->testProjectPath.'/file2.php');
        mkdir($this->testProjectPath.'/subfolder');

        $result = getFilesInFolder($this->project, $this->testProjectPath);

        $this->assertStringContainsString('file1.txt (File', $result);
        $this->assertStringContainsString('file2.php (File', $result);
        $this->assertStringContainsString('subfolder (Folder)', $result);
    }

    public function test_get_files_in_folder_returns_error_for_missing_folder()
    {
        $result = getFilesInFolder($this->project, '/nonexistent/folder');

        $this->assertEquals('Error: folder does not exist', $result);
    }

    public function test_update_project_description()
    {
        $result = updateProjectDescription($this->project, 'New description');

        $this->assertEquals('Description updated.', $result);
        $this->project->refresh();
        $this->assertEquals('New description', $this->project->description);
    }

    public function test_update_project_technical_specs()
    {
        $result = updateProjectTechnicalSpecs($this->project, 'New specs');

        $this->assertEquals('Specs updated.', $result);
        $this->project->refresh();
        $this->assertEquals('New specs', $this->project->technical_specs);
    }

    public function test_update_project_system_description()
    {
        $result = updateProjectSystemDescription($this->project, 'New system');

        $this->assertEquals('Description updated.', $result);
        $this->project->refresh();
        $this->assertEquals('New system', $this->project->system_description);
    }

    public function test_update_project_notes()
    {
        $result = updateProjectNotes($this->project, 'New notes');

        $this->assertEquals('Notes updated.', $result);
        $this->project->refresh();
        $this->assertEquals('New notes', $this->project->notes);
    }

    public function test_update_project_tasks()
    {
        $result = updateProjectTasks($this->project, '✅ Task 1\n⬜ Task 2');

        $this->assertEquals('Tasks updated.', $result);
        $this->project->refresh();
        $this->assertEquals('✅ Task 1\n⬜ Task 2', $this->project->tasks);
    }

    public function test_update_project_info_routes_correctly()
    {
        $result = updateProjectInfo($this->project, 'description', 'Updated via router');

        $this->assertEquals('Description updated.', $result);
        $this->project->refresh();
        $this->assertEquals('Updated via router', $this->project->description);
    }

    public function test_update_project_info_returns_error_for_invalid_field()
    {
        $result = updateProjectInfo($this->project, 'invalid_field', 'Some content');

        $this->assertEquals('Invalid whatToUpdate', $result);
    }

    public function test_get_content_from_url_fetches_body()
    {
        Http::fake([
            'https://example.com' => Http::response('<html><body>Test content</body></html>'),
        ]);

        $result = getContentFromUrl($this->project, 'https://example.com');

        $this->assertStringContainsString('Test content', $result);
    }

    public function test_exec_with_timeout_executes_command()
    {
        $result = execWithTimeout('echo "Hello World"', 5, $this->testProjectPath);

        $this->assertFalse($result['timed_out']);
        $this->assertStringContainsString('Hello World', $result['output']);
        $this->assertEquals(0, $result['code']);
    }

    public function test_exec_with_timeout_handles_timeout()
    {
        // This command should timeout
        $result = execWithTimeout('sleep 10', 1, $this->testProjectPath);

        $this->assertTrue($result['timed_out']);
    }

    public function test_exec_with_timeout_captures_error_code()
    {
        $result = execWithTimeout('exit 1', 5, $this->testProjectPath);

        $this->assertFalse($result['timed_out']);
        $this->assertEquals(1, $result['code']);
    }

    public function test_run_shell_command_success()
    {
        $result = runShellCommand($this->project, 'echo "Test output"');

        $this->assertStringContainsString('Test output', $result);
    }

    public function test_run_shell_command_with_errors()
    {
        $result = runShellCommand($this->project, 'nonexistentcommand');

        $this->assertStringContainsString('Errors running command', $result);
    }

    public function test_run_shell_command_timeout_detection()
    {
        // Mock a command that would timeout
        $result = runShellCommand($this->project, 'sleep 30', 5);

        $this->assertStringContainsString('timed out', $result);
    }

    public function test_run_shell_command_limits_output_lines()
    {
        // Create a command that outputs many lines
        $command = 'for i in {1..200}; do echo "Line $i"; done';

        $result = runShellCommand($this->project, $command, 50);

        $lines = explode("\n", $result);
        $this->assertLessThanOrEqual(50, count($lines));
    }

    public function test_run_shell_command_returns_success_for_empty_output()
    {
        $result = runShellCommand($this->project, 'true');

        $this->assertEquals('Success.', $result);
    }

    public function test_get_available_functions_returns_array()
    {
        $functions = getAvailableFunctions();

        $this->assertIsArray($functions);
        $this->assertNotEmpty($functions);
    }

    public function test_get_available_functions_has_required_structure()
    {
        $functions = getAvailableFunctions();

        foreach ($functions as $function) {
            $this->assertArrayHasKey('name', $function);
            $this->assertArrayHasKey('description', $function);
            $this->assertArrayHasKey('input_schema', $function);
        }
    }

    public function test_get_available_functions_includes_all_tools()
    {
        $functions = getAvailableFunctions();
        $names = array_column($functions, 'name');

        $expectedTools = [
            'searchForFile',
            'updateProjectInfo',
            'runShellCommand',
            'getContentsFromFile',
            'saveDataToFile',
            'getTreeFolderStructure',
            'getFilesInFolder',
            'getContentFromUrl',
        ];

        foreach ($expectedTools as $tool) {
            $this->assertContains($tool, $names);
        }
    }
}
