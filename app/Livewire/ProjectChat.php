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
    public $debug = false;


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
        $message = trim($this->newMessage);
        $this->newMessage = '';

        if ($message === '\\debug') {
            $this->debug = !$this->debug;
            return;
        }

        if ($message === '\\delete') {
            $message = Message::where('project_id', $this->project->id)
                ->orderBy('id', 'desc')
                ->first();
            $message?->delete();
            $this->loadMessages();
            return;
        }

        SendMessageJob::dispatch($this->project, $message);

    }

    public function render()
    {
        return view('livewire.project-chat');
    }
}