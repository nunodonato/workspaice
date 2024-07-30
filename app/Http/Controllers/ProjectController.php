<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::all();

        return view('projects.index', compact('projects'));
    }

    public function create()
    {
        return view('projects.create');
    }

    public function show(Project $project)
    {
        return view('projects.show', compact('project'));
    }

    public function edit(Project $project)
    {
        return view('projects.edit', compact('project'));
    }

    public function update(Request $request, Project $project)
    {
        $validatedData = $request->validate([
            'name' => 'required|max:255',
            'description' => 'required',
        ]);

        $project->update($validatedData);

        return redirect()->route('projects.show', $project)->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project)
    {
        $project->messages()->delete();
        $project->calls()->delete();
        $dirPath = $project->full_path;
        if (file_exists($dirPath)) {
            exec("rm -rf $dirPath");
        }
        $project->delete();

        return redirect()->route('projects')->with('success', 'Project deleted successfully.');
    }

    public function chat(Project $project)
    {
        return view('projects.show', compact('project'));
    }

    public function restoreSnapshot(Project $project, $snapshotId)
    {
        $snapshot = $project->snapshots()->find($snapshotId);
        if (! $snapshot) {
            abort(404);
        }

        $path = $project->full_path;
        shell_exec("cd $path && git reset --hard ".$snapshot->commit);
        shell_exec("cd $path && git clean -fd");
        // delete all snapshots after this one
        $project->snapshots()->where('id', '>', $snapshotId)->delete();

        // delete all messages after this snapshot
        $project->messages()->where('created_at', '>', $snapshot->created_at)->delete();

        // flash success message
        session()->flash('snapshot', 'Project restored to snapshot');

        return redirect(route('projects.show', $project));
    }
}
