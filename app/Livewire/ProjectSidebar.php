<?php

namespace App\Livewire;

use App\Models\Project;
use Livewire\Component;

class ProjectSidebar extends Component
{
    public $project;

    public function mount(Project $project)
    {
        $this->project = $project;
    }

    public function render()
    {
        return view('livewire.project-sidebar');
    }
}