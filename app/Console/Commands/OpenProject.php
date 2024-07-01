<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\AIService;
use Illuminate\Console\Command;

class OpenProject extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'project:open';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Opens and begins working on a project';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (Project::count() === 0) {
            $this->error('No projects found. Please create a project first.');
            exit;
        }

        // first, ask which project the user wants to open
        $project = $this->choice(
            'Which project do you want to open?',
            \App\Models\Project::all()->pluck('name')->toArray()
        );
        // then, get the project from the database

        $project = \App\Models\Project::where('name', $project)->first();
        $this->newLine();
        $this->info("{$project->name}");
        $this->line("Description: {$project->description}");
        $this->newLine();

        $ai = new AIService($project);

        if ($project->messages()->count() == 0) {
            $ai->sendMessage('hello');
        }

        while(true) {
            $input = trim($this->ask('Instruction'));
            if (strlen($input)>0 && $input[0] == '\\') {
                $input = substr($input, 1);
                $this->parseSystemCommand($input);
            } else {
                $ai->sendMessage($input, 'user');
            }

        }


    }

    public function parseSystemCommand(string $input): void
    {
        if ($input === 'exit' || $input === 'quit' || $input === 'q') {
            $this->line('Goodbye!');
            exit;
        }
    }
}
