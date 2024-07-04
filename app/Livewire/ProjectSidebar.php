<?php

namespace App\Livewire;

use App\Models\Project;
use Livewire\Component;

class ProjectSidebar extends Component
{
    public $project;
    public $tasks;
    public $snapshots;

    public function mount(Project $project)
    {
        $this->project = $project;
        $this->prepareTasks();
        $this->snapshots = $project->snapshots()->orderBy('created_at', 'desc')->get();
    }

    public function prepareTasks()
    {
        // find [X] and replace with ✅
        // find [>] and replace with ➡️
        // find [ ] and replace with ⬜

        /** @var string $originalTasks */
        $originalTasks = $this->project->tasks;
        $this->tasks = preg_replace('/\[X\]/', '✅', $originalTasks);
        $this->tasks = preg_replace('/\[>\]/', '➡️', $this->tasks);
        $this->tasks = preg_replace('/\[ \]/', '⬜', $this->tasks);
    }

    public function render()
    {
        $this->prepareTasks();
        return view('livewire.project-sidebar');
    }

    public function openFolder()
    {
        $escapedPath = escapeshellarg($this->project->full_path);

        if (PHP_OS_FAMILY === 'Linux') {
            $command = "xdg-open $escapedPath";
        } elseif (PHP_OS_FAMILY === 'Darwin') {
            $command = "open $escapedPath";
        } elseif (PHP_OS_FAMILY === 'Windows') {
            $command = "explorer $escapedPath";
        } else {

        }

        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);
    }

    public function openTerminal(): void
    {
        $path = $this->project->full_path;

        if (!is_dir($path)) {
            throw new \RuntimeException("Invalid directory path");
        }

        $escapedPath = escapeshellarg($path);

        switch (PHP_OS_FAMILY) {
            case 'Linux':
                // Try to detect the desktop environment
                $desktopSession = getenv('DESKTOP_SESSION');
                $xdgCurrentDesktop = getenv('XDG_CURRENT_DESKTOP');

                if (stripos($desktopSession, 'gnome') !== false || stripos($xdgCurrentDesktop, 'gnome') !== false) {
                    $command = "gnome-terminal --working-directory=$escapedPath";
                } elseif (stripos($desktopSession, 'kde') !== false || stripos($xdgCurrentDesktop, 'kde') !== false) {
                    $command = "konsole --workdir $escapedPath";
                } elseif (stripos($desktopSession, 'xfce') !== false || stripos($xdgCurrentDesktop, 'xfce') !== false) {
                    $command = "xfce4-terminal --working-directory=$escapedPath";
                } else {
                    // Fallback to x-terminal-emulator if available
                    $command = "x-terminal-emulator -e 'cd $escapedPath && exec $SHELL'";
                }
                break;

            case 'Darwin':
                $command = "open -a Terminal $escapedPath";
                break;

            case 'Windows':
                $command = "start cmd.exe /K \"cd /d $escapedPath\"";
                break;

            default:
                throw new \RuntimeException("Unsupported operating system");
        }

        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            throw new \RuntimeException("Failed to open terminal: " . implode("\n", $output));
        }
    }

    public function deleteProject()
    {
        $this->redirect(route('projects.delete', $this->project));
    }

    public function createSnapshot()
    {
        $path = $this->project->full_path;
        $commitMessage = now()->format('Y-m-d H:i:s');
        shell_exec("cd $path && git add .");
        $commitOutput = shell_exec("cd $path && git commit -m '" . $commitMessage . "'");

        if (preg_match('/\[.* ([a-f0-9]+)\]/', $commitOutput, $matches)) {
            $commitId = $matches[1];
            $this->project->snapshots()->create([
                'commit' => $commitId,
                'description' => $commitMessage,
            ]);
        } else {
            // flash error message
            session()->flash('snapshot', 'Failed to create snapshot. No changes?');
        }
        $this->snapshots = $this->project->snapshots()->orderBy('created_at', 'desc')->get();
    }

    public function restoreSnapshot($snapshotId)
    {
        $snapshot = $this->project->snapshots()->find($snapshotId);
        if (!$snapshot) {
            return;
        }

        $path = $this->project->full_path;
        shell_exec("cd $path && git reset --hard " . $snapshot->commit);
        shell_exec("cd $path && git clean -fd");
        // delete all snapshots after this one
        $this->project->snapshots()->where('id', '>', $snapshotId)->delete();
        $this->snapshots = $this->project->snapshots()->orderBy('created_at', 'desc')->get();

        // delete all messages after this snapshot
        $this->project->messages()->where('created_at', '>', $snapshot->created_at)->delete();

        // flash success message
        session()->flash('snapshot', 'Project restored to snapshot');
        $this->dispatch('snapshot-restored');
    }
}
