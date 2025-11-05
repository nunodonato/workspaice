<?php

namespace Tests\Unit;

use App\Jobs\SendMessageJob;
use App\Models\Image;
use App\Models\Message;
use App\Models\Project;
use App\Models\Setting;
use App\Services\AIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class SendMessageJobTest extends TestCase
{
    use RefreshDatabase;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test project
        $this->project = Project::factory()->create([
            'name' => 'Test Project',
            'full_path' => '/tmp/test-project',
        ]);

        // Set API key
        Setting::setSetting('api_key', 'test-api-key');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_job_can_be_instantiated()
    {
        $job = new SendMessageJob($this->project, 'Test message');

        $this->assertInstanceOf(SendMessageJob::class, $job);
    }

    public function test_constructor_creates_user_message()
    {
        $job = new SendMessageJob($this->project, 'Hello AI');

        $message = Message::where('project_id', $this->project->id)
            ->where('role', 'user')
            ->first();

        $this->assertNotNull($message);
        $this->assertEquals('Hello AI', $message->content);
    }

    public function test_constructor_creates_images_for_message()
    {
        $images = [
            [
                'media_type' => 'image/jpeg',
                'content' => 'base64-encoded-image-data',
            ],
            [
                'media_type' => 'image/png',
                'content' => 'another-base64-encoded-image',
            ],
        ];

        $job = new SendMessageJob($this->project, 'Check this image', $images);

        $message = Message::where('project_id', $this->project->id)
            ->where('role', 'user')
            ->first();

        $this->assertNotNull($message);
        $this->assertEquals(2, $message->images()->count());

        $imageRecords = $message->images;
        $this->assertEquals('image/jpeg', $imageRecords[0]->media_type);
        $this->assertEquals('base64-encoded-image-data', $imageRecords[0]->content);
    }

    public function test_handle_calls_ai_service()
    {
        // Create a user message first
        Message::create([
            'content' => 'Test message',
            'role' => 'user',
            'project_id' => $this->project->id,
        ]);

        // Mock the AIService
        $mockAIService = Mockery::mock('overload:'.AIService::class);
        $mockAIService->shouldReceive('sendMessage')
            ->once()
            ->with(null);

        $job = new SendMessageJob($this->project, 'Test message');
        $job->handle();

        // Assertions are done through Mockery expectations
        $this->assertTrue(true);
    }

    public function test_handle_creates_error_message_on_exception()
    {
        // Create a mock that throws an exception
        $mockAIService = Mockery::mock('overload:'.AIService::class);
        $mockAIService->shouldReceive('sendMessage')
            ->andThrow(new \Exception('API Error'));

        $job = new SendMessageJob($this->project, 'Test message');
        $job->handle();

        $errorMessage = Message::where('project_id', $this->project->id)
            ->where('role', 'error')
            ->first();

        $this->assertNotNull($errorMessage);
        $this->assertEquals('API Error', $errorMessage->content);
    }

    public function test_job_has_correct_timeout()
    {
        $job = new SendMessageJob($this->project, 'Test message');

        $this->assertEquals(90 * 5, $job->timeout);
    }

    public function test_job_can_be_queued()
    {
        Queue::fake();

        SendMessageJob::dispatch($this->project, 'Test message');

        Queue::assertPushed(SendMessageJob::class);
    }

    public function test_job_can_be_queued_with_images()
    {
        Queue::fake();

        $images = [
            [
                'media_type' => 'image/jpeg',
                'content' => 'base64-data',
            ],
        ];

        SendMessageJob::dispatch($this->project, 'Test message', $images);

        Queue::assertPushed(SendMessageJob::class, function ($job) use ($images) {
            return $job->project->id === $this->project->id;
        });
    }

    public function test_multiple_messages_can_be_created()
    {
        new SendMessageJob($this->project, 'First message');
        new SendMessageJob($this->project, 'Second message');

        $messages = Message::where('project_id', $this->project->id)
            ->where('role', 'user')
            ->get();

        $this->assertCount(2, $messages);
        $this->assertEquals('First message', $messages[0]->content);
        $this->assertEquals('Second message', $messages[1]->content);
    }

    public function test_job_uses_queue_traits()
    {
        $job = new SendMessageJob($this->project, 'Test message');

        $this->assertObjectHasProperty('job', $job);
    }
}
