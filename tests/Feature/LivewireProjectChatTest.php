<?php

namespace Tests\Feature;

use App\Jobs\SendMessageJob;
use App\Livewire\ProjectChat;
use App\Models\Message;
use App\Models\Project;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class LivewireProjectChatTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->project = Project::factory()->create([
            'name' => 'Test Project',
            'full_path' => '/tmp/test-project',
        ]);

        Setting::setSetting('default_debug', false);
    }

    public function test_component_can_mount()
    {
        Livewire::test(ProjectChat::class, ['project' => $this->project])
            ->assertStatus(200);
    }

    public function test_component_loads_messages()
    {
        Message::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'role' => 'user',
        ]);

        Livewire::test(ProjectChat::class, ['project' => $this->project])
            ->assertSet('messages', function ($messages) {
                return count($messages) === 3;
            });
    }

    public function test_send_message_dispatches_job()
    {
        Queue::fake();

        Livewire::test(ProjectChat::class, ['project' => $this->project])
            ->set('newMessage', 'Hello AI')
            ->call('sendMessage');

        Queue::assertPushed(SendMessageJob::class);
    }

    public function test_send_message_validates_required_field()
    {
        Livewire::test(ProjectChat::class, ['project' => $this->project])
            ->set('newMessage', '')
            ->call('sendMessage')
            ->assertHasErrors(['newMessage']);
    }

    public function test_send_message_clears_input_after_sending()
    {
        Queue::fake();

        Livewire::test(ProjectChat::class, ['project' => $this->project])
            ->set('newMessage', 'Test message')
            ->call('sendMessage')
            ->assertSet('newMessage', '');
    }

    public function test_debug_command_toggles_debug_mode()
    {
        Livewire::test(ProjectChat::class, ['project' => $this->project])
            ->assertSet('debug', false)
            ->set('newMessage', '\\debug')
            ->call('sendMessage')
            ->assertSet('debug', true)
            ->set('newMessage', '\\debug')
            ->call('sendMessage')
            ->assertSet('debug', false);
    }

    public function test_delete_command_removes_last_message_pair()
    {
        Message::create([
            'content' => 'User message',
            'role' => 'user',
            'project_id' => $this->project->id,
        ]);

        Message::create([
            'content' => 'Assistant response',
            'role' => 'assistant',
            'project_id' => $this->project->id,
        ]);

        $this->assertEquals(2, Message::where('project_id', $this->project->id)->count());

        Livewire::test(ProjectChat::class, ['project' => $this->project])
            ->set('newMessage', '\\delete')
            ->call('sendMessage');

        $this->assertEquals(0, Message::where('project_id', $this->project->id)->count());
    }

    public function test_stop_command_sets_cache_flag()
    {
        Livewire::test(ProjectChat::class, ['project' => $this->project])
            ->set('newMessage', '\\stop')
            ->call('sendMessage');

        $this->assertTrue(Cache::has('stop-'.$this->project->id));
    }

    public function test_stop_command_deletes_non_user_non_assistant_messages()
    {
        Message::create([
            'content' => 'searchForFile',
            'role' => 'tool_use',
            'project_id' => $this->project->id,
        ]);

        Livewire::test(ProjectChat::class, ['project' => $this->project])
            ->set('newMessage', '\\stop')
            ->call('sendMessage');

        $this->assertEquals(0, Message::where('project_id', $this->project->id)->count());
    }

    public function test_invalid_slash_command_is_ignored()
    {
        Queue::fake();

        Livewire::test(ProjectChat::class, ['project' => $this->project])
            ->set('newMessage', '\\invalid')
            ->call('sendMessage');

        Queue::assertNothingPushed();
    }

    public function test_loading_state_is_true_when_waiting_for_response()
    {
        Message::create([
            'content' => 'User message',
            'role' => 'user',
            'project_id' => $this->project->id,
        ]);

        Livewire::test(ProjectChat::class, ['project' => $this->project])
            ->assertSet('loading', true);
    }

    public function test_loading_state_is_false_when_assistant_responded()
    {
        Message::create([
            'content' => 'User message',
            'role' => 'user',
            'project_id' => $this->project->id,
        ]);

        Message::create([
            'content' => 'Assistant response',
            'role' => 'assistant',
            'project_id' => $this->project->id,
        ]);

        Livewire::test(ProjectChat::class, ['project' => $this->project])
            ->assertSet('loading', false);
    }

    public function test_cannot_send_message_while_loading()
    {
        Queue::fake();

        Message::create([
            'content' => 'User message',
            'role' => 'user',
            'project_id' => $this->project->id,
        ]);

        Livewire::test(ProjectChat::class, ['project' => $this->project])
            ->set('newMessage', 'Another message')
            ->call('sendMessage');

        Queue::assertNothingPushed();
    }

    public function test_images_can_be_uploaded()
    {
        Storage::fake('local');

        $image = UploadedFile::fake()->image('test.jpg', 100, 100);

        Livewire::test(ProjectChat::class, ['project' => $this->project])
            ->set('images', [$image])
            ->assertHasNoErrors();
    }

    public function test_image_validation_rejects_large_files()
    {
        Storage::fake('local');

        // Create a file larger than 1536KB
        $largeImage = UploadedFile::fake()->image('large.jpg')->size(2000);

        Livewire::test(ProjectChat::class, ['project' => $this->project])
            ->set('images', [$largeImage])
            ->assertHasErrors(['images.0']);
    }

    public function test_image_validation_rejects_invalid_formats()
    {
        Storage::fake('local');

        $invalidFile = UploadedFile::fake()->create('document.pdf', 100);

        Livewire::test(ProjectChat::class, ['project' => $this->project])
            ->set('images', [$invalidFile])
            ->assertHasErrors(['images.0']);
    }

    public function test_remove_image_removes_from_array()
    {
        Storage::fake('local');

        $image1 = UploadedFile::fake()->image('test1.jpg');
        $image2 = UploadedFile::fake()->image('test2.jpg');

        Livewire::test(ProjectChat::class, ['project' => $this->project])
            ->set('images', [$image1, $image2])
            ->call('removeImage', 0)
            ->assertSet('images', function ($images) {
                return count($images) === 1;
            });
    }

    public function test_message_with_images_dispatches_job_with_image_data()
    {
        Queue::fake();
        Storage::fake('local');

        $image = UploadedFile::fake()->image('test.jpg');

        Livewire::test(ProjectChat::class, ['project' => $this->project])
            ->set('newMessage', 'Check this image')
            ->set('images', [$image])
            ->call('sendMessage');

        Queue::assertPushed(SendMessageJob::class);
    }

    public function test_images_are_cleared_after_sending()
    {
        Queue::fake();
        Storage::fake('local');

        $image = UploadedFile::fake()->image('test.jpg');

        Livewire::test(ProjectChat::class, ['project' => $this->project])
            ->set('newMessage', 'Test')
            ->set('images', [$image])
            ->call('sendMessage')
            ->assertSet('images', []);
    }

    public function test_first_message_id_is_set_on_mount()
    {
        Message::factory()->create([
            'project_id' => $this->project->id,
            'role' => 'user',
        ]);

        $firstMessage = $this->project->messages->first();

        Livewire::test(ProjectChat::class, ['project' => $this->project])
            ->assertSet('firstMessageId', $firstMessage->id);
    }

    public function test_debug_mode_uses_setting_default()
    {
        Setting::setSetting('default_debug', true);

        Livewire::test(ProjectChat::class, ['project' => $this->project])
            ->assertSet('debug', true);
    }

    public function test_messages_are_limited_to_100()
    {
        Message::factory()->count(150)->create([
            'project_id' => $this->project->id,
            'role' => 'user',
        ]);

        Livewire::test(ProjectChat::class, ['project' => $this->project])
            ->assertSet('messages', function ($messages) {
                return count($messages) === 100;
            });
    }

    public function test_send_message_clears_stop_cache()
    {
        Queue::fake();
        Cache::put('stop-'.$this->project->id, true, 60);

        Livewire::test(ProjectChat::class, ['project' => $this->project])
            ->set('newMessage', 'Hello')
            ->call('sendMessage');

        $this->assertFalse(Cache::has('stop-'.$this->project->id));
    }
}
