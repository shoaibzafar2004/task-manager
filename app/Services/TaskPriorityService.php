<?php

namespace App\Services;

use App\Models\Task;
use Illuminate\Support\Facades\DB;

/**
 * Keeps active task priorities as a gapless 1..n sequence (1 = highest).
 */
class TaskPriorityService
{
    public function create(array $attributes, ?int $position = null): Task
    {
        return DB::transaction(function () use ($attributes, $position) {
            $bottom = $this->nextPriority();
            $position = $this->clamp($position ?? $bottom, $bottom);

            Task::active()->where('priority', '>=', $position)->increment('priority');

            return Task::create([...$attributes, 'priority' => $position]);
        });
    }

    public function move(Task $task, int $position): void
    {
        if ($task->isCompleted()) {
            return;
        }

        DB::transaction(function () use ($task, $position) {
            $position = $this->clamp($position, $this->nextPriority() - 1);
            $current = $task->priority;

            if ($position < $current) {
                Task::active()->whereBetween('priority', [$position, $current - 1])->increment('priority');
            } elseif ($position > $current) {
                Task::active()->whereBetween('priority', [$current + 1, $position])->decrement('priority');
            }

            $task->update(['priority' => $position]);
        });
    }

    /**
     * Re-assign the priority slots currently held by the given tasks in the new order.
     * Reordering a filtered subset therefore leaves every other task where it was.
     *
     * @param  array<int, int>  $orderedIds
     * @return array<int, int> task id => new priority
     */
    public function reorder(array $orderedIds): array
    {
        return DB::transaction(function () use ($orderedIds) {
            $tasks = Task::active()->whereIn('id', $orderedIds)->get()->keyBy('id');
            $slots = $tasks->pluck('priority')->sort()->values();

            $result = [];
            foreach (array_values(array_filter($orderedIds, fn ($id) => $tasks->has($id))) as $i => $id) {
                $tasks[$id]->update(['priority' => $slots[$i]]);
                $result[$id] = $slots[$i];
            }

            return $result;
        });
    }

    public function complete(Task $task): void
    {
        if ($task->isCompleted()) {
            return;
        }

        DB::transaction(function () use ($task) {
            $task->update(['completed_at' => now()]);
            $this->closeGap($task->priority);
        });
    }

    public function uncomplete(Task $task): void
    {
        if (! $task->isCompleted()) {
            return;
        }

        DB::transaction(fn () => $task->update([
            'completed_at' => null,
            'priority' => $this->nextPriority(),
        ]));
    }

    public function delete(Task $task): void
    {
        DB::transaction(function () use ($task) {
            $task->delete();

            if (! $task->isCompleted()) {
                $this->closeGap($task->priority);
            }
        });
    }

    public function restore(Task $task): void
    {
        DB::transaction(function () use ($task) {
            if (! $task->isCompleted()) {
                $task->priority = $this->nextPriority();
            }

            $task->restore();
        });
    }

    private function closeGap(int $priority): void
    {
        Task::active()->where('priority', '>', $priority)->decrement('priority');
    }

    private function nextPriority(): int
    {
        return (int) Task::active()->max('priority') + 1;
    }

    private function clamp(int $position, int $max): int
    {
        return max(1, min($position, max(1, $max)));
    }
}
