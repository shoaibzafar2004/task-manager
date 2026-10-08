<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Services\TaskPriorityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskPriorityTest extends TestCase
{
    use RefreshDatabase;

    private TaskPriorityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TaskPriorityService::class);
    }

    /** @return array<string, int> title => priority, for active tasks */
    private function order(): array
    {
        return Task::active()->orderBy('priority')->pluck('priority', 'title')->all();
    }

    private function make(string ...$titles): void
    {
        foreach ($titles as $title) {
            $this->service->create(['title' => $title]);
        }
    }

    public function test_new_tasks_go_to_the_bottom(): void
    {
        $this->make('A', 'B', 'C');

        $this->assertSame(['A' => 1, 'B' => 2, 'C' => 3], $this->order());
    }

    public function test_creating_at_a_position_shifts_others_down(): void
    {
        $this->make('A', 'B', 'C');
        $this->service->create(['title' => 'X'], 2);

        $this->assertSame(['A' => 1, 'X' => 2, 'B' => 3, 'C' => 4], $this->order());
    }

    public function test_out_of_range_position_is_clamped(): void
    {
        $this->make('A', 'B');
        $this->service->create(['title' => 'X'], 99);

        $this->assertSame(['A' => 1, 'B' => 2, 'X' => 3], $this->order());
    }

    public function test_move_up_and_down(): void
    {
        $this->make('A', 'B', 'C', 'D');

        $this->service->move(Task::firstWhere('title', 'D'), 1);
        $this->assertSame(['D' => 1, 'A' => 2, 'B' => 3, 'C' => 4], $this->order());

        $this->service->move(Task::firstWhere('title', 'D'), 3);
        $this->assertSame(['A' => 1, 'B' => 2, 'D' => 3, 'C' => 4], $this->order());
    }

    public function test_reorder_reassigns_priorities(): void
    {
        $this->make('A', 'B', 'C');
        $ids = Task::pluck('id', 'title');

        $this->postJson(route('tasks.reorder'), ['ids' => [$ids['C'], $ids['A'], $ids['B']]])
            ->assertOk()
            ->assertJsonPath("priorities.{$ids['C']}", 1);

        $this->assertSame(['C' => 1, 'A' => 2, 'B' => 3], $this->order());
    }

    public function test_reordering_a_filtered_project_keeps_other_tasks_in_place(): void
    {
        $p = Project::factory()->create();
        $this->service->create(['title' => 'P1', 'project_id' => $p->id]);
        $this->service->create(['title' => 'Other1']);
        $this->service->create(['title' => 'P2', 'project_id' => $p->id]);
        $this->service->create(['title' => 'Other2']);
        $this->service->create(['title' => 'P3', 'project_id' => $p->id]);
        $ids = Task::pluck('id', 'title');

        $this->service->reorder([$ids['P3'], $ids['P1'], $ids['P2']]);

        $this->assertSame(['P3' => 1, 'Other1' => 2, 'P1' => 3, 'Other2' => 4, 'P2' => 5], $this->order());
    }

    public function test_completing_closes_the_gap_and_reopening_goes_to_bottom(): void
    {
        $this->make('A', 'B', 'C');
        $b = Task::firstWhere('title', 'B');

        $this->patchJson(route('tasks.toggle', $b))->assertOk()->assertJson(['completed' => true]);
        $this->assertSame(['A' => 1, 'C' => 2], $this->order());
        $this->assertNotNull($b->fresh()->completed_at);

        $this->patchJson(route('tasks.toggle', $b))->assertOk()->assertJson(['completed' => false]);
        $this->assertSame(['A' => 1, 'C' => 2, 'B' => 3], $this->order());
    }

    public function test_delete_closes_the_gap_and_restore_goes_to_bottom(): void
    {
        $this->make('A', 'B', 'C');
        $a = Task::firstWhere('title', 'A');

        $this->delete(route('tasks.destroy', $a))->assertRedirect();
        $this->assertSoftDeleted($a);
        $this->assertSame(['B' => 1, 'C' => 2], $this->order());

        $this->patch(route('tasks.restore', $a->id))->assertRedirect();
        $this->assertSame(['B' => 1, 'C' => 2, 'A' => 3], $this->order());
    }
}
