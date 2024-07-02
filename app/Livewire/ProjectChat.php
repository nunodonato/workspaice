<?php

namespace App\Livewire;

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
            ->where('id', '>', $this->lastMessageId)
            ->orderBy('id', 'asc')
            ->get();

        foreach ($newMessages as $message) {
            $this->messages[] = $message;
            $this->lastMessageId = $message->id;
        }

        // Keep only the last 50 messages
        $this->messages = array_slice($this->messages, -50);
    }

    public function sendMessage()
    {
        $this->validate();
        $message = $this->newMessage;
        $this->newMessage = '';
        $ai = new AIService($this->project);
        $ai->sendMessage(trim($message));
        return 1;
    }

    public function render()
    {
        return view('livewire.project-chat');
    }
}
