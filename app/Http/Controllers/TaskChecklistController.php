<?php

namespace App\Http\Controllers;

use App\Http\Requests\ToggleChecklistItemRequest;
use App\Models\Task;
use App\Services\Checklist;
use Illuminate\Http\JsonResponse;

class TaskChecklistController extends Controller
{
    /**
     * Tick or untick one checklist entry in the task's details.
     *
     * The client sends the version of the details it rendered; if they changed since (another
     * tab, an edit), indices may point at different lines, so the request is refused.
     */
    public function __invoke(ToggleChecklistItemRequest $request, Task $task, Checklist $checklist): JsonResponse
    {
        if (! hash_equals($task->checklistVersion(), $request->validated('version'))) {
            return response()->json(['message' => 'This task was changed elsewhere. Reload to see the latest version.'], 409);
        }

        $index = $request->integer('index');

        if (! array_key_exists($index, $checklist->items($task->info))) {
            return response()->json(['message' => 'That checklist item no longer exists.'], 422);
        }

        $task->update(['info' => $checklist->set($task->info, $index, $request->boolean('checked'))]);

        return response()->json([
            'version' => $task->checklistVersion(),
            ...$checklist->progress($task->info),
        ]);
    }
}
