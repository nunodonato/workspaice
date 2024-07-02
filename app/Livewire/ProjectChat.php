<?php

namespace App\Livewire;

use App\Jobs\SendMessageJob;
use App\Models\Project;
use App\Models\Message;
use App\Services\AIService;
use Livewire\Component;

class ProjectChat extends Component
{
    public $project;
    public $newMessage = '';
    public $messages = [];
    public $lastMessageId = 0;

    protected $rules = [
        'newMessage' => 'required|min:1',
    ];

    public function mount(Project $project)
    {
        $this->project = $project;
        $this->loadMessages();

    }

    public function loadMessages()
    {
        $newMessages = Message::where('project_id', $this->project->id)
            ->orderBy('id', 'desc')
            ->take(100)->get();

        $this->messages = [];
        foreach ($newMessages as $message) {
            $this->messages[] = $message;
        }
    }

    public function sendMessage()
    {
        $this->validate();
        $message = $this->newMessage;
        $this->newMessage = '';

        SendMessageJob::dispatch($this->project, $message);

    }

    public function render()
    {
        return view('livewire.project-chat');
    }
}
