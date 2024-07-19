<?php

namespace App\Livewire;

use App\Jobs\SendMessageJob;
use App\Models\Project;
use App\Models\Message;
use App\Models\Setting;
use App\Services\AIService;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProjectChat extends Component
{
    use WithFileUploads;

    public $project;
    public $newMessage = '';
    public $messages = [];
    public $lastMessageId = 0;
    public $debug = false;
    public $firstMessageId = 0;
    public $loading = false;

    public $images = [];


    protected $rules = [
        'newMessage' => 'required_without:images|min:1',
        'images.*' => 'image|max:1536|mimes:jpg,png,gif,webp',
    ];

    public function mount(Project $project)
    {
        $this->project = $project;
        $this->loadMessages();
        $this->firstMessageId = $project->messages->first()?->id;
        $this->debug = Setting::getSetting('default_debug', false);

    }

    #[On('snapshot-restored')]
    public function loadMessages()
    {
        $newMessages = Message::where('project_id', $this->project->id)
            ->orderBy('id', 'desc')
            ->take(100)->get();

        $mostRecent = $newMessages->first();
        if($mostRecent && !in_array($mostRecent->role, ['assistant', 'error'])) {
            $this->loading = true;
        } else {
            $this->loading = false;
        }

        $this->messages = [];
        foreach ($newMessages as $message) {
            $this->messages[] = $message;
        }

        if (!$this->firstMessageId) {
            $this->firstMessageId = $this->project->messages->first()?->id;
        }

    }

    public function updatedImages()
    {
        $this->validate([
            'images.*' => 'image|max:1536|mimes:jpg,png,gif,webp',
        ]);

    }

    public function removeImage($index)
    {
        unset($this->images[$index]);
        $this->images = array_values($this->images);
    }

    public function sendMessage()
    {
        Cache::forget('stop-'.$this->project->id);
        $this->validate();
        $message = trim($this->newMessage);

        if ($message === '\\debug') {
            $this->newMessage = '';
            $this->debug = !$this->debug;
            return;
        }

        if ($message === '\\delete') {
            $this->newMessage = '';
            $message = Message::where('project_id', $this->project->id)
                ->orderBy('id', 'desc')
                ->first();
            $message?->delete();

            $message = Message::where('project_id', $this->project->id)
                ->orderBy('id', 'desc')
                ->first();
            if ($message->role === 'user') {
                $message->delete();
            }

            $this->loadMessages();
            return;
        }


        if ($message === '\\stop') {
            Cache::put('stop-'.$this->project->id, true, 60);
            $this->newMessage = '';
            $this->loading = false;

            // delete most recent messages if role != assistant and != user
            delete:
            $message = Message::where('project_id', $this->project->id)
                ->orderBy('id', 'desc')
                ->first();

            if($message && !in_array($message->role, ['assistant', 'user'])) {
                $message->delete();
                goto delete;
            }
            $this->loadMessages();

            return;
        }

        if (strpos($message, '\\') === 0) {
            $this->newMessage = '';
            return;
        }

        if ($this->loading) {
            return;
        }

        $this->newMessage = '';
        $imageData = [];
        foreach($this->images as $image) {
            $mimeType = $image->getMimeType();
            $base64 = base64_encode($image->get());
            $imageData[] = [
                'media_type' => $mimeType,
                'content' => $base64,
            ];
            $image->delete();
        }
        SendMessageJob::dispatch($this->project, $message, $imageData);
        $this->images = [];
    }

    public function render()
    {
        $this->loadMessages();
        return view('livewire.project-chat');
    }
}
