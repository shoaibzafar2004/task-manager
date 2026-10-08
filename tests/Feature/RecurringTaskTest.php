<?php

namespace Tests\Feature;

use App\Enums\Recurrence;
use App\Models\Label;
use App\Models\Project;
use App\Models\Task;
use App\Services\TaskPriorityService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RecurringTaskTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: Recurrence, 1: string, 2: string}>
     */
    public static function nextDates(): array
    {
        // Today is Thursday 2026-10-08 in every case.
        return [
            'daily, due today' => [Recurrence::Daily, '2026-10-08', '2026-10-09'],
            'daily, five days overdue' => [Recurrence::Daily, '2026-10-03', '2026-10-09'],
            'weekly, due today' => [Recurrence::Weekly, '2026-10-08', '2026-10-15'],
            'weekly, a week overdue' => [Recurrence::Weekly, '2026-10-01', '2026-10-15'],
            'monthly, due today' => [Recurrence::Monthly, '2026-10-08', '2026-11-08'],
            'monthly from the 31st' => [Recurrence::Monthly, '2026-08-31', '2026-10-31'],
            'weekly, due in the future' => [Recurrence::Weekly, '2026-10-20', '2026-10-27'],
        ];
    }

    #[DataProvider('nextDates')]
    public function test_next_due_date_is_the_first_repeat_after_today(Recurrence $recurrence, string $due, string $expected): void
    {
        $next = $recurrence->nextAfter(CarbonImmutable::parse($due), CarbonImmutable::parse('2026-10-08'));

        $this->assertSame($expected, $next->toDateString());
    }

    public function test_completing_a_repeating_task_creates_the_next_one_in_its_place(): void
    {
        $this->travelTo('2026-10-08 10:00');
        $priorities = app(TaskPriorityService::class);
        $project = Project::factory()->create();
        $label = Label::factory()->create();

        $priorities->create(['title' => 'First']);
        $weekly = $priorities->create([
            'title' => 'Water plants',
            'info' => "- [x] Kitchen\n- [x] Balcony",
            'project_id' => $project->id,
            'due_date' => '2026-10-08',
            'recurrence' => Recurrence::Weekly,
        ]);
        $weekly->labels()->attach($label);
        $priorities->create(['title' => 'Last']);

        $response = $this->patchJson(route('tasks.toggle', $weekly))->assertOk()->assertJsonPath('completed', true);

        $next = Task::active()->where('title', 'Water plants')->sole();
        $this->assertNotSame($weekly->id, $next->id);
        $this->assertSame('2026-10-15', $next->due_date->toDateString());
        $this->assertSame(Recurrence::Weekly, $next->recurrence);
        $this->assertSame($project->id, $next->project_id);
        $this->assertSame([$label->id], $next->labels->modelKeys());
        $this->assertSame("- [ ] Kitchen\n- [ ] Balcony", $next->info);

        // Same position as before, so the list order doesn't change.
        $this->assertSame(['First' => 1, 'Water plants' => 2, 'Last' => 3], Task::active()->orderBy('priority')->pluck('priority', 'title')->all());

        $response->assertJsonPath('next.id', $next->id);
        $this->assertStringContainsString('data-id="'.$next->id.'"', $response->json('next.html'));
    }

    public function test_repeating_task_without_due_date_repeats_from_today(): void
    {
        $this->travelTo('2026-10-08 10:00');
        $task = Task::factory()->create(['recurrence' => Recurrence::Daily]);

        $this->patchJson(route('tasks.toggle', $task));

        $this->assertSame('2026-10-09', Task::active()->sole()->due_date->toDateString());
    }

    public function test_one_off_tasks_do_not_repeat(): void
    {
        $task = Task::factory()->create();

        $this->patchJson(route('tasks.toggle', $task))->assertJsonPath('next', null);

        $this->assertSame(0, Task::active()->count());
    }

    public function test_reopening_a_task_does_not_create_another(): void
    {
        $task = Task::factory()->completed()->create(['recurrence' => Recurrence::Daily]);

        $this->patchJson(route('tasks.toggle', $task))->assertJsonPath('completed', false);

        $this->assertSame(1, Task::count());
    }

    public function test_completing_from_the_task_page_mentions_the_next_due_date(): void
    {
        $this->travelTo('2026-10-08 10:00');
        $task = Task::factory()->create(['due_date' => '2026-10-08', 'recurrence' => Recurrence::Monthly]);

        $this->from(route('tasks.show', $task))
            ->patch(route('tasks.toggle', $task))
            ->assertSessionHas('status', 'Task completed. Next one due Nov 8.');
    }

    public function test_repeat_can_be_set_and_is_validated(): void
    {
        $this->post(route('tasks.store'), ['title' => 'Standup', 'recurrence' => 'daily'])->assertSessionHasNoErrors();
        $this->assertSame(Recurrence::Daily, Task::sole()->recurrence);

        $this->post(route('tasks.store'), ['title' => 'Bad', 'recurrence' => 'hourly'])->assertSessionHasErrors('recurrence');
    }

    public function test_cards_show_the_repeat_badge(): void
    {
        Task::factory()->create(['recurrence' => Recurrence::Weekly]);

        $this->get(route('tasks.index'))->assertSee('Repeats weekly');
    }
}
