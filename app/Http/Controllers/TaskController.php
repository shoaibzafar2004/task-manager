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

    /**
     * How many open tasks are loaded per batch.
     */
    public const PER_PAGE = 100;

    /**
     * Show open tasks in priority order, one batch at a time.
     *
     * Batches are keyed on priority (`?after=N`) rather than page numbers, so completing or
     * reordering tasks between batches never skips or repeats a task. JSON requests get
     * the next batch's rendered cards for the "load more" script.
     */
    public function index(Request $request): View|JsonResponse
    {
        $projectId = $request->integer('project') ?: null;
        $search = trim($request->string('q')) ?: null;

        $query = Task::active()->forProject($projectId)->search($search);

        $tasks = $query->clone()
            ->with('project')
            ->where('priority', '>', $request->integer('after'))
            ->orderBy('priority')
            ->limit(self::PER_PAGE + 1)
            ->get();

        $hasMore = $tasks->count() > self::PER_PAGE;
        $tasks = $tasks->take(self::PER_PAGE);

        // The client appends `after` from its last visible card, which stays correct as tasks are completed.
        $nextUrl = $hasMore ? route('tasks.index', ['project' => $projectId, 'q' => $search]) : null;

        if ($request->wantsJson()) {
            return response()->json([
                'html' => view('tasks.partials.cards', ['tasks' => $tasks])->render(),
                'next_url' => $nextUrl,
            ]);
        }

        return view('tasks.index', [
            'tasks' => $tasks,
            'nextUrl' => $nextUrl,
            'total' => $query->count(),
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
