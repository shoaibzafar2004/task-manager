<?php

namespace Tests\Feature;

use App\Models\Label;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_open_task_shows_all_its_details_and_actions(): void
    {
        $project = Project::factory()->create(['name' => 'Launch']);
        $task = Task::factory()
            ->for($project)
            ->hasAttached(Label::factory()->state(['name' => 'urgent-ish']))
            ->create(['title' => 'Write release notes', 'info' => 'Cover every new feature.', 'due_date' => '2026-12-01']);

        $this->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSee('Write release notes')
            ->assertSee('Cover every new feature.')
            ->assertSee('Launch')
            ->assertSee('urgent-ish')
            ->assertSee('Dec 1, 2026')
            ->assertSee('Mark complete')
            ->assertSee(route('tasks.edit', $task))
            ->assertDontSee('Restore task');
    }

    public function test_completed_task_offers_reopen(): void
    {
        $task = Task::factory()->completed()->create();

        $this->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSee('Completed')
            ->assertSee('Reopen task')
            ->assertDontSee('Mark complete');
    }

    public function test_deleted_task_can_be_viewed_and_only_restored(): void
    {
        $task = Task::factory()->create(['title' => 'Old idea']);
        $task->delete();

        $this->get(route('tasks.show', $task->id))
            ->assertOk()
            ->assertSee('Old idea')
            ->assertSee('Restore task')
            ->assertDontSee('Mark complete')
            ->assertDontSee(route('tasks.edit', $task));
    }

    public function test_missing_task_is_not_found(): void
    {
        $this->get(route('tasks.show', 999))->assertNotFound();
    }

    public function test_toggle_from_a_form_redirects_back(): void
    {
        $task = Task::factory()->create();

        $this->from(route('tasks.show', $task))
            ->patch(route('tasks.toggle', $task))
            ->assertRedirect(route('tasks.show', $task))
            ->assertSessionHas('status', 'Task completed.');

        $this->assertTrue($task->fresh()->isCompleted());
    }

    public function test_editing_from_the_task_page_returns_to_it(): void
    {
        $task = Task::factory()->create();

        $this->put(route('tasks.update', $task), ['title' => 'Renamed', 'from' => 'show'])
            ->assertRedirect(route('tasks.show', $task));
    }

    public function test_task_titles_link_to_the_task_page(): void
    {
        $open = Task::factory()->create();
        $done = Task::factory()->completed()->create(['priority' => 2]);

        $this->get(route('tasks.index'))->assertSee(route('tasks.show', $open));
        $this->get(route('history'))->assertSee(route('tasks.show', $done));
    }
}
