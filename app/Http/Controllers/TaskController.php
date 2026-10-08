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
     * Due-date views shown as tabs on the task list, besides "All".
     */
    public const VIEWS = ['today', 'upcoming'];

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
        $view = in_array($request->query('view'), self::VIEWS, true) ? $request->query('view') : null;

        $filtered = Task::active()->forProject($projectId)->withLabel($labelId)->search($search);
        $query = $filtered->clone()->dueIn($view);

        $tasks = $query->clone()
            ->with(['project', 'labels'])
            ->where('priority', '>', $request->integer('after'))
            ->orderBy('priority')
            ->limit(self::PER_PAGE + 1)
            ->get();

        $hasMore = $tasks->count() > self::PER_PAGE;
        $tasks = $tasks->take(self::PER_PAGE);

        // The client appends `after` from its last visible card, which stays correct as tasks are completed.
        $nextUrl = $hasMore ? route('tasks.index', ['view' => $view, 'project' => $projectId, 'label' => $labelId, 'q' => $search]) : null;

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
            'view' => $view,
            // Tab counts follow the project, label and search filters currently applied.
            'viewCounts' => [
                'all' => $view === null ? null : $filtered->clone()->count(),
                'today' => $filtered->clone()->dueIn('today')->count(),
                'overdue' => $filtered->clone()->whereDate('due_date', '<', today())->count(),
                'upcoming' => $filtered->clone()->dueIn('upcoming')->count(),
            ],
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
     *
     * The list and History open tasks in a popup and ask for JSON, which carries just the
     * task's card. Visiting the URL directly renders the full page.
     */
    public function show(Request $request, Task $task): View|JsonResponse
    {
        $task->load(['project', 'labels']);

        if ($request->wantsJson()) {
            return response()->json([
                'title' => $task->title,
                'html' => view('tasks.partials.detail', ['task' => $task, 'modal' => true])->render(),
            ]);
        }

        [$backUrl, $backLabel] = $this->backLink($request, $task);

        return view('tasks.show', ['task' => $task, 'backUrl' => $backUrl, 'backLabel' => $backLabel]);
    }

    /**
     * Where the task page's "back" link goes: the list or History page the user came from.
     *
     * The origin is remembered in the session, so it survives completing or reopening the
     * task on the page. Without one (a direct link), it follows the task's state.
     *
     * @return array{0: string, 1: string}
     */
    private function backLink(Request $request, Task $task): array
    {
        $previous = $request->headers->get('referer') ?? $request->session()->previousUrl();

        if ($previous
            && parse_url($previous, PHP_URL_HOST) === $request->getHost()
            && in_array(parse_url($previous, PHP_URL_PATH) ?? '/', ['/', '/history'], true)) {
            $request->session()->put('tasks.back', $previous);
        }

        $url = $request->session()->get('tasks.back') ?? match (true) {
            $task->trashed() => route('history', ['tab' => 'deleted']),
            $task->isCompleted() => route('history'),
            default => route('tasks.index'),
        };

        return [$url, parse_url($url, PHP_URL_PATH) === '/history' ? 'History' : 'Tasks'];
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

    public function destroy(Request $request, Task $task): RedirectResponse|JsonResponse
    {
        $this->priorities->delete($task);

        return $request->wantsJson()
            ? response()->json(['deleted' => true])
            : back()->with('status', 'Task deleted.');
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

    public function restore(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $this->priorities->restore(Task::onlyTrashed()->findOrFail($id));

        return $request->wantsJson()
            ? response()->json(['restored' => true])
            : back()->with('status', 'Task restored.');
    }
}
