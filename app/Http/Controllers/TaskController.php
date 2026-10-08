<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReorderTasksRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Label;
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
        $labelId = $request->integer('label') ?: null;
        $search = trim($request->string('q')) ?: null;

        $query = Task::active()->forProject($projectId)->withLabel($labelId)->search($search);

        $tasks = $query->clone()
            ->with(['project', 'labels'])
            ->where('priority', '>', $request->integer('after'))
            ->orderBy('priority')
            ->limit(self::PER_PAGE + 1)
            ->get();

        $hasMore = $tasks->count() > self::PER_PAGE;
        $tasks = $tasks->take(self::PER_PAGE);

        // The client appends `after` from its last visible card, which stays correct as tasks are completed.
        $nextUrl = $hasMore ? route('tasks.index', ['project' => $projectId, 'label' => $labelId, 'q' => $search]) : null;

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
            'labels' => Label::withCount(['tasks' => fn ($q) => $q->active()])->orderBy('name')->get(),
            'projectId' => $projectId,
            'labelId' => $labelId,
            'search' => $search,
        ]);
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $task = $this->priorities->create(
            $request->safe()->only(['title', 'info', 'due_date', 'project_id']),
            $request->validated('priority'),
        );

        $task->labels()->sync($request->validated('labels') ?? []);

        return back()->with('status', 'Task created.');
    }

    /**
     * Show one task in full. Deleted tasks can be viewed too, so History entries open.
     */
    public function show(Task $task): View
    {
        return view('tasks.show', ['task' => $task->load(['project', 'labels'])]);
    }

    public function edit(Task $task): View
    {
        return view('tasks.edit', [
            'task' => $task,
            'projects' => Project::orderBy('name')->get(),
            'labels' => Label::orderBy('name')->get(),
            'maxPriority' => max(1, Task::active()->count()),
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $task->update($request->safe()->only(['title', 'info', 'due_date', 'project_id']));
        $task->labels()->sync($request->validated('labels') ?? []);

        if ($request->filled('priority')) {
            $this->priorities->move($task, $request->integer('priority'));
        }

        $redirect = match (true) {
            $request->input('from') === 'show' => redirect()->route('tasks.show', $task),
            $task->isCompleted() => redirect()->route('history'),
            default => redirect()->route('tasks.index'),
        };

        return $redirect->with('status', 'Task updated.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->priorities->delete($task);

        return back()->with('status', 'Task deleted.');
    }

    /**
     * Complete or reopen a task. The list uses JSON; the task page submits a plain form.
     */
    public function toggle(Request $request, Task $task): JsonResponse|RedirectResponse
    {
        $task->isCompleted() ? $this->priorities->uncomplete($task) : $this->priorities->complete($task);

        if ($request->wantsJson()) {
            return response()->json(['completed' => $task->isCompleted(), 'priority' => $task->priority]);
        }

        return back()->with('status', $task->isCompleted() ? 'Task completed.' : 'Task reopened.');
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
