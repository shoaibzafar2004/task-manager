<?php

namespace App\Services;

use App\Models\Task;
use Illuminate\Support\Facades\DB;

/**
 * Creates the next occurrence when a repeating task is completed.
 */
class RecurringTasks
{
    public function __construct(
        private TaskPriorityService $priorities,
        private Checklist $checklist,
    ) {}

    /**
     * Copy a just-completed repeating task with its next due date, unticked checklist and
     * labels, back into the position the completed task held. Returns null for one-off tasks.
     */
    public function scheduleNext(Task $completed, int $position): ?Task
    {
        if ($completed->recurrence === null) {
            return null;
        }

        return DB::transaction(function () use ($completed, $position) {
            $next = $this->priorities->create([
                'title' => $completed->title,
                'info' => $this->checklist->uncheckAll($completed->info),
                'project_id' => $completed->project_id,
                'recurrence' => $completed->recurrence,
                'due_date' => $completed->recurrence->nextAfter($completed->due_date ?? today(), today()),
            ], $position);

            $next->labels()->sync($completed->labels()->pluck('labels.id'));

            return $next;
        });
    }
}
