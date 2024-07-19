<?php

namespace App\Livewire;

use App\Jobs\SendMessageJob;
use App\Models\Project;
use App\Models\Message;
use App\Models\Setting;
use App\Services\AIService;
use Illuminate\Support\Facades\Cache;
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

    private $job = null;


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
        SendMessageJob::dispatch($this->project, $message);

    }

    public function render()
    {
        $this->loadMessages();
        return view('livewire.project-chat');
    }
}
