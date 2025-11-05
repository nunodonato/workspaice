<?php

namespace Tests\Unit;

use App\Models\Call;
use App\Models\Message;
use App\Models\Project;
use App\Models\Setting;
use App\Services\AIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use NunoDonato\AnthropicAPIPHP\Client;
use NunoDonato\AnthropicAPIPHP\Messages;
use Tests\TestCase;

class AIServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Project $project;
    protected $mockClient;
    protected AIService $aiService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test project
        $this->project = Project::factory()->create([
            'name' => 'Test Project',
            'full_path' => '/tmp/test-project',
            'description' => 'Test description',
            'technical_specs' => 'Test specs',
            'system_description' => 'Test system',
            'tasks' => 'Test tasks',
            'notes' => 'Test notes',
        ]);

        // Create a mock Anthropic client
        $this->mockClient = Mockery::mock(Client::class);

        // Set API key
        Setting::setSetting('api_key', 'test-api-key');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_constructor_initializes_with_api_key_from_settings()
    {
        Setting::setSetting('api_key', 'test-key-123');

        $service = new AIService($this->project);

        $this->assertInstanceOf(AIService::class, $service);
        $this->assertEquals($this->project->id, $service->project->id);
    }

    public function test_append_message_creates_message_record()
    {
        $service = new AIService($this->project);

        $message = $service->appendMessage('Hello AI', 'user');

        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals('Hello AI', $message->content);
        $this->assertEquals('user', $message->role);
        $this->assertEquals($this->project->id, $message->project_id);
    }

    public function test_append_message_with_tool_data()
    {
        $service = new AIService($this->project);

        $message = $service->appendMessage(
            'searchForFile',
            'tool_use',
            'tool_123',
            'tool_123',
            ['string' => 'test.php']
        );

        $this->assertEquals('searchForFile', $message->content);
        $this->assertEquals('tool_use', $message->role);
        $this->assertEquals('tool_123', $message->name);
        $this->assertEquals('tool_123', $message->tool_id);
        $this->assertEquals(['string' => 'test.php'], $message->input);
    }

    public function test_get_system_message_includes_project_information()
    {
        // Create the system prompt file
        $promptPath = resource_path('prompts/system.txt');
        if (!file_exists(dirname($promptPath))) {
            mkdir(dirname($promptPath), 0755, true);
        }
        file_put_contents($promptPath, 'You are a helpful AI assistant.');

        $service = new AIService($this->project);
        $systemMessage = $service->getSystemMessage();

        $this->assertStringContainsString('You are a helpful AI assistant.', $systemMessage);
        $this->assertStringContainsString('<ProjectInformation>', $systemMessage);
        $this->assertStringContainsString('Test Project', $systemMessage);
        $this->assertStringContainsString('/tmp/test-project', $systemMessage);
        $this->assertStringContainsString('Test description', $systemMessage);
        $this->assertStringContainsString('Test specs', $systemMessage);
    }

    public function test_get_meta_content_includes_all_metadata()
    {
        $service = new AIService($this->project);
        $metaContent = $service->getMetaContent();

        $this->assertStringContainsString('<SystemInformation>', $metaContent);
        $this->assertStringContainsString('Test system', $metaContent);
        $this->assertStringContainsString('<Tasks>', $metaContent);
        $this->assertStringContainsString('Test tasks', $metaContent);
        $this->assertStringContainsString('<Notes>', $metaContent);
        $this->assertStringContainsString('Test notes', $metaContent);
    }

    public function test_get_previous_messages_returns_correct_format()
    {
        $service = new AIService($this->project);

        // Create some test messages
        Message::create([
            'content' => 'First message',
            'role' => 'user',
            'project_id' => $this->project->id,
        ]);

        Message::create([
            'content' => 'Second message',
            'role' => 'assistant',
            'project_id' => $this->project->id,
        ]);

        $messages = $service->getPreviousMessages(10);

        $this->assertCount(2, $messages);
        $this->assertEquals('First message', $messages[0]['content']);
        $this->assertEquals('user', $messages[0]['role']);
        $this->assertEquals('Second message', $messages[1]['content']);
        $this->assertEquals('assistant', $messages[1]['role']);
    }

    public function test_get_previous_messages_excludes_error_messages()
    {
        $service = new AIService($this->project);

        Message::create([
            'content' => 'User message',
            'role' => 'user',
            'project_id' => $this->project->id,
        ]);

        Message::create([
            'content' => 'Error occurred',
            'role' => 'error',
            'project_id' => $this->project->id,
        ]);

        $messages = $service->getPreviousMessages(10);

        $this->assertCount(1, $messages);
        $this->assertEquals('User message', $messages[0]['content']);
    }

    public function test_trim_big_messages_truncates_long_content()
    {
        $service = new AIService($this->project);

        $longMessage = [
            'content' => str_repeat('a', 1500),
            'role' => 'tool_result',
        ];

        $trimmed = $service->trimBigMessages($longMessage);

        $this->assertEquals('[removed due to length]', $trimmed['content']);
    }

    public function test_trim_big_messages_truncates_long_input()
    {
        $service = new AIService($this->project);

        $messageWithLongInput = [
            'content' => 'test',
            'role' => 'tool_use',
            'input' => ['data' => str_repeat('a', 1500)],
        ];

        $trimmed = $service->trimBigMessages($messageWithLongInput);

        $this->assertEquals(['input' => 'removed due to length'], $trimmed['input']);
    }

    public function test_prepare_message_content_for_tool_use()
    {
        $service = new AIService($this->project);

        $message = [
            'role' => 'tool_use',
            'content' => 'searchForFile',
            'tool_id' => 'tool_123',
            'input' => ['string' => 'test.php'],
        ];

        $prepared = $service->prepareMessageContent($message);

        $this->assertEquals('tool_use', $prepared['type']);
        $this->assertEquals('searchForFile', $prepared['name']);
        $this->assertEquals('tool_123', $prepared['id']);
        $this->assertEquals(['string' => 'test.php'], $prepared['input']);
    }

    public function test_prepare_message_content_for_tool_result()
    {
        $service = new AIService($this->project);

        $message = [
            'role' => 'tool_result',
            'content' => 'File found: test.php',
            'tool_id' => 'tool_123',
        ];

        $prepared = $service->prepareMessageContent($message);

        $this->assertEquals('tool_result', $prepared['type']);
        $this->assertEquals('File found: test.php', $prepared['content']);
        $this->assertEquals('tool_123', $prepared['tool_use_id']);
    }

    public function test_handle_last_message_appends_metadata()
    {
        $service = new AIService($this->project);

        $message = [
            'content' => 'Hello',
            'role' => 'user',
        ];

        $handled = $service->handleLastMessage($message);

        $this->assertStringContainsString('<SystemInformation>', $handled['content']);
        $this->assertStringContainsString('<Tasks>', $handled['content']);
        $this->assertStringContainsString('<Notes>', $handled['content']);
        $this->assertStringContainsString('<user>Hello</user>', $handled['content']);
    }

    public function test_handle_last_message_with_array_content()
    {
        $service = new AIService($this->project);

        $message = [
            'content' => [
                ['type' => 'text', 'content' => 'Hello'],
                ['type' => 'text', 'content' => 'World'],
            ],
            'role' => 'user',
        ];

        $handled = $service->handleLastMessage($message);

        $this->assertIsArray($handled['content']);
        $this->assertStringContainsString('<user>World</user>', $handled['content'][1]['content']);
    }
}
