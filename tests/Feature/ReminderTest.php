<?php

namespace Tests\Feature;

use App\Http\Controllers\ReminderController;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_reminders_list_open_tasks_due_today_or_overdue(): void
    {
        $this->travelTo('2026-10-08 09:00');
        $overdue = Task::factory()->create(['title' => 'Pay rent', 'due_date' => '2026-10-03', 'priority' => 1]);
        $today = Task::factory()->create(['title' => 'Call bank', 'due_date' => '2026-10-08', 'priority' => 2]);
        Task::factory()->create(['title' => 'Tomorrow', 'due_date' => '2026-10-09', 'priority' => 3]);
        Task::factory()->create(['title' => 'Someday', 'priority' => 4]);
        Task::factory()->completed()->create(['title' => 'Done already', 'due_date' => '2026-10-08', 'priority' => 5]);
        Task::factory()->create(['title' => 'Deleted', 'due_date' => '2026-10-08', 'priority' => 6])->delete();

        $this->getJson(route('reminders'))
            ->assertOk()
            ->assertExactJson(['tasks' => [
                ['id' => $overdue->id, 'title' => 'Pay rent', 'overdue' => true, 'due' => 'Overdue since Oct 3', 'url' => route('tasks.show', $overdue)],
                ['id' => $today->id, 'title' => 'Call bank', 'overdue' => false, 'due' => 'Due today', 'url' => route('tasks.show', $today)],
            ]]);
    }

    public function test_reminders_are_capped_and_ordered_by_priority(): void
    {
        $this->freezeTime();
        $tasks = Task::factory()
            ->count(ReminderController::LIMIT + 5)
            ->sequence(fn ($sequence) => ['priority' => $sequence->index + 1])
            ->create(['due_date' => today()]);

        $this->getJson(route('reminders'))
            ->assertJsonCount(ReminderController::LIMIT, 'tasks')
            ->assertJsonPath('tasks.0.id', $tasks->first()->id);
    }

    public function test_layout_shows_the_reminder_bell(): void
    {
        $this->get(route('tasks.index'))->assertSee('x-data="reminders(', false);
    }
}
