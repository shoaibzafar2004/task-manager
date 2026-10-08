<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function __invoke(Request $request): View
    {
        $projectId = $request->integer('project') ?: null;
        $tab = $request->query('tab') === 'deleted' ? 'deleted' : 'completed';

        $query = $tab === 'deleted'
            ? Task::onlyTrashed()->latest('deleted_at')
            : Task::completed()->latest('completed_at');

        return view('history.index', [
            'tasks' => $query->with('project')->forProject($projectId)->paginate(20)->withQueryString(),
            'projects' => Project::orderBy('name')->get(),
            'projectId' => $projectId,
            'tab' => $tab,
            'counts' => [
                'completed' => Task::completed()->forProject($projectId)->count(),
                'deleted' => Task::onlyTrashed()->forProject($projectId)->count(),
            ],
        ]);
    }
}
