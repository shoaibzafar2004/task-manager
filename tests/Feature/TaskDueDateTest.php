<?php

namespace Tests\Feature;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TaskDueDateTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_can_be_created_and_updated_with_a_due_date(): void
    {
        $this->post(route('tasks.store'), ['title' => 'Ship it', 'due_date' => '2026-12-24'])
            ->assertSessionHasNoErrors();

        $task = Task::firstWhere('title', 'Ship it');
        $this->assertSame('2026-12-24', $task->due_date->toDateString());

        $this->put(route('tasks.update', $task), ['title' => 'Ship it', 'due_date' => ''])
            ->assertSessionHasNoErrors();

        $this->assertNull($task->fresh()->due_date);
    }

    public function test_due_date_must_be_a_date(): void
    {
        $this->post(route('tasks.store'), ['title' => 'Ship it', 'due_date' => 'not-a-date'])
            ->assertSessionHasErrors('due_date');
    }

    /**
     * @return array<string, array{0: int|null, 1: string|null}>
     */
    public static function dueDates(): array
    {
        return [
            'no due date' => [null, null],
            'yesterday' => [-1, 'overdue'],
            'today' => [0, 'today'],
            'in two days' => [2, 'soon'],
            'in three days' => [3, 'later'],
        ];
    }

    #[DataProvider('dueDates')]
    public function test_due_status_reflects_days_left(?int $daysFromToday, ?string $expected): void
    {
        $this->freezeTime();

        $task = Task::factory()->make([
            'due_date' => $daysFromToday === null ? null : today()->addDays($daysFromToday),
        ]);

        $this->assertSame($expected, $task->dueStatus());
    }

    public function test_index_shows_due_labels(): void
    {
        $this->freezeTime();
        Task::factory()->create(['title' => 'Late', 'due_date' => today()->subDay()]);
        Task::factory()->create(['title' => 'Now', 'due_date' => today(), 'priority' => 2]);
        Task::factory()->create(['title' => 'Next', 'due_date' => today()->addDay(), 'priority' => 3]);

        $this->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('Overdue')
            ->assertSee('Due today')
            ->assertSee('Due tomorrow');
    }
}
