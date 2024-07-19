<?php

namespace App\Livewire;

use App\Jobs\SendMessageJob;
use App\Models\Project;
use App\Models\Setting;
use Illuminate\Support\Str;
use Livewire\Component;

class ProjectCreation extends Component
{
    public $name = '';
    public $description = '';
    public $specs = '';
    public $system = '';
    public $autostart = '';

    protected $rules = [
        'name' => 'required|min:3|max:255',
        'description' => 'required|min:20',
        'specs' => 'required|min:10',
        'system' => 'required|min:10',
        'autostart' => 'boolean'
    ];

    public function mount()
    {
        $this->system = Setting::getSetting('system_info');
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function createProject()
    {

        $validatedData = $this->validate();
        $autoStart = $validatedData['autostart'];

        $slug = Str::slug($this->name);
        $homeDir = getenv('HOME');
        $suggestedPath = "{$homeDir}/projects/{$slug}";

        if (!is_dir($suggestedPath)) {
            mkdir($suggestedPath, 0777, true);
        }

        if ($autoStart) {
            $tasks = "[>] Scan the project directory for existing files and folders
[ ] Ask clarifying questions and rewrite the requirements as needed
[ ] Consider the challenges for the project and make notes on how to overcome them
[ ] Assess the availability of resources and tools needed for the project in the system
[ ] Build a plan of action and get user approval
[ ] Replace this task list with the new plan";
        } else {
            $tasks = "[ ] Discuss the project with the user";
        }

        $project = new Project();
        $project->name = $this->name;
        $project->description = $this->description;
        $project->technical_specs = $this->specs;
        $project->system_description = $this->system;
        $project->full_path = $suggestedPath;
        $project->slug = $slug;
        $project->notes = "";
        $project->tasks = $tasks;
        $project->save();

        if ($autoStart) {
            SendMessageJob::dispatch($project, 'Hello', []);
        }

        session()->flash('message', 'Project created successfully.');

        $path = $project->full_path;
        $cmd = "cd $path && git init";
        shell_exec($cmd);

        return redirect()->route('projects.show', $project);
    }

    public function render()
    {
        return view('livewire.project-creation');
    }
}
