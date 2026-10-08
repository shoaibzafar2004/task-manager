<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\JsonResponse;

class ReminderController extends Controller
{
    /**
     * How many tasks one reminder check returns at most.
     */
    public const LIMIT = 20;

    /**
     * Open tasks that are due today or overdue, for the browser's reminder notifications.
     */
    public function __invoke(): JsonResponse
    {
        $tasks = Task::active()
            ->whereDate('due_date', '<=', today())
            ->orderBy('priority')
            ->limit(self::LIMIT)
            ->get(['id', 'title', 'due_date']);

        return response()->json([
            'tasks' => $tasks->map(fn (Task $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'overdue' => $task->due_date->isBefore(today()),
                'due' => $task->due_date->isToday() ? 'Due today' : 'Overdue since '.$task->due_date->format('M j'),
                'url' => route('tasks.show', $task),
            ]),
        ]);
    }
}
