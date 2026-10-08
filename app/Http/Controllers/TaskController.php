<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReorderTasksRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Project;
use App\Models\Task;
use App\Services\TaskPriorityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function __construct(private TaskPriorityService $priorities) {}

    public function index(Request $request): View
    {
        $projectId = $request->integer('project') ?: null;
        $search = trim($request->string('q')) ?: null;

        return view('tasks.index', [
            'tasks' => Task::with('project')->active()->forProject($projectId)->search($search)->orderBy('priority')->get(),
            'projects' => Project::withCount(['tasks' => fn ($q) => $q->active()])->orderBy('name')->get(),
            'projectId' => $projectId,
            'search' => $search,
        ]);
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $this->priorities->create(
            $request->safe()->only(['title', 'info', 'due_date', 'project_id']),
            $request->validated('priority'),
        );

        return back()->with('status', 'Task created.');
    }

    public function edit(Task $task): View
    {
        return view('tasks.edit', [
            'task' => $task,
            'projects' => Project::orderBy('name')->get(),
            'maxPriority' => max(1, Task::active()->count()),
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $task->update($request->safe()->only(['title', 'info', 'due_date', 'project_id']));

        if ($request->filled('priority')) {
            $this->priorities->move($task, $request->integer('priority'));
        }

        return redirect()->route($task->isCompleted() ? 'history' : 'tasks.index')->with('status', 'Task updated.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->priorities->delete($task);

        return back()->with('status', 'Task deleted.');
    }

    public function toggle(Task $task): JsonResponse
    {
        $task->isCompleted() ? $this->priorities->uncomplete($task) : $this->priorities->complete($task);

        return response()->json(['completed' => $task->isCompleted(), 'priority' => $task->priority]);
    }

    public function reorder(ReorderTasksRequest $request): JsonResponse
    {
        return response()->json(['priorities' => $this->priorities->reorder($request->validated('ids'))]);
    }

    public function restore(int $id): RedirectResponse
    {
        $this->priorities->restore(Task::onlyTrashed()->findOrFail($id));

        return back()->with('status', 'Task restored.');
    }
}
