<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;

class ProjectController extends Controller
{
    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = Project::create($request->validated());

        return redirect()->route('tasks.index', ['project' => $project->id])
            ->with('status', "Project \"{$project->name}\" created.");
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()->route('tasks.index')->with('status', 'Project deleted. Its tasks were kept without a project.');
    }
}
