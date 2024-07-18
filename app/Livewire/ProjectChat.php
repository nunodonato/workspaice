<?php

namespace App\Livewire;

use App\Jobs\SendMessageJob;
use App\Models\Project;
use App\Models\Message;
use App\Models\Setting;
use App\Services\AIService;
use Livewire\Component;

class ProjectChat extends Component
{
    public $project;
    public $newMessage = '';
    public $messages = [];
    public $lastMessageId = 0;
    public $debug = false;
    public $firstMessageId = 0;
    public $loading = false;


    protected $rules = [
        'newMessage' => 'required|min:1',
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
        if($mostRecent && $mostRecent->role != 'assistant') {
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

        if (strpos($message, '\\') === 0) {
            return;
        }

        SendMessageJob::dispatch($this->project, $message);

    }

    public function render()
    {
        $this->loadMessages();
        return view('livewire.project-chat');
    }
}
