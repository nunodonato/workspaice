<?php

namespace App\Livewire;

use App\Models\Project;
use Livewire\Component;

class ProjectCreation extends Component
{
    public $name = '';
    public $description = '';

    protected $rules = [
        'name' => 'required|min:3|max:255',
        'description' => 'required|min:10',
    ];

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function createProject()
    {
        $validatedData = $this->validate();

        $project = Project::create($validatedData);

        session()->flash('message', 'Project created successfully.');

        return redirect()->route('projects.show', $project);
    }

    public function render()
    {
        return view('livewire.project-creation');
    }
}