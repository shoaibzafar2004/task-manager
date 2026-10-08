<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryController extends Controller
{
    /**
     * How many history entries are loaded per batch.
     */
    public const PER_PAGE = 20;

    /**
     * Show completed or deleted tasks, newest first, one batch at a time.
     *
     * Cursor pagination keeps batches stable when a task is reopened or deleted from the
     * list in between. JSON requests get the next batch for the "load more" script.
     */
    public function __invoke(Request $request): View|JsonResponse
    {
        $projectId = $request->integer('project') ?: null;
        $search = trim($request->string('q')) ?: null;
        $tab = $request->query('tab') === 'deleted' ? 'deleted' : 'completed';

        $query = $tab === 'deleted'
            ? Task::onlyTrashed()->latest('deleted_at')
            : Task::completed()->latest('completed_at');

        $tasks = $query->latest('id')
            ->with('project')
            ->forProject($projectId)
            ->search($search)
            ->cursorPaginate(self::PER_PAGE)
            ->withQueryString();

        if ($request->wantsJson()) {
            return response()->json([
                'html' => view('history.partials.items', ['tasks' => $tasks, 'tab' => $tab])->render(),
                'next_url' => $tasks->nextPageUrl(),
            ]);
        }

        return view('history.index', [
            'tasks' => $tasks,
            'projects' => Project::orderBy('name')->get(),
            'projectId' => $projectId,
            'search' => $search,
            'tab' => $tab,
            'counts' => [
                'completed' => Task::completed()->forProject($projectId)->search($search)->count(),
                'deleted' => Task::onlyTrashed()->forProject($projectId)->search($search)->count(),
            ],
        ]);
    }
}
