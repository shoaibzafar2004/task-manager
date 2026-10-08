<?php

namespace Tests\Feature;

use App\Http\Controllers\TaskController;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskViewsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * One open task per due-date situation, plus a completed one that must never show.
     */
    private function tasksAroundToday(): void
    {
        $this->freezeTime();

        Task::factory()->create(['title' => 'Was due last week', 'due_date' => today()->subWeek(), 'priority' => 1]);
        Task::factory()->create(['title' => 'Due today', 'due_date' => today(), 'priority' => 2]);
        Task::factory()->create(['title' => 'Due tomorrow', 'due_date' => today()->addDay(), 'priority' => 3]);
        Task::factory()->create(['title' => 'Due in a week', 'due_date' => today()->addDays(7), 'priority' => 4]);
        Task::factory()->create(['title' => 'Due in eight days', 'due_date' => today()->addDays(8), 'priority' => 5]);
        Task::factory()->create(['title' => 'No due date', 'priority' => 6]);
        Task::factory()->completed()->create(['title' => 'Finished early', 'due_date' => today(), 'priority' => 7]);
    }

    public function test_today_shows_overdue_and_due_today(): void
    {
        $this->tasksAroundToday();

        $this->get(route('tasks.index', ['view' => 'today']))
            ->assertOk()
            ->assertSeeInOrder(['Was due last week', 'Due today'])
            ->assertDontSee('Due tomorrow')
            ->assertDontSee('No due date')
            ->assertDontSee('Finished early');
    }

    public function test_upcoming_shows_the_next_seven_days(): void
    {
        $this->tasksAroundToday();

        $this->get(route('tasks.index', ['view' => 'upcoming']))
            ->assertOk()
            ->assertSeeInOrder(['Due tomorrow', 'Due in a week'])
            ->assertDontSee('Due today')
            ->assertDontSee('Was due last week')
            ->assertDontSee('Due in eight days');
    }

    public function test_tabs_show_counts_and_flag_overdue(): void
    {
        $this->tasksAroundToday();

        $this->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('title="1 overdue"', false)
            ->assertSeeInOrder(['All', '6', 'Today', '2', 'Upcoming', '2']);
    }

    public function test_unknown_view_falls_back_to_all_tasks(): void
    {
        $this->tasksAroundToday();

        $this->get(route('tasks.index', ['view' => 'someday']))
            ->assertOk()
            ->assertSee('No due date')
            ->assertSee('Due in eight days');
    }

    public function test_views_combine_with_project_filter(): void
    {
        $this->freezeTime();
        $project = Project::factory()->create();
        Task::factory()->for($project)->create(['title' => 'Project task due today', 'due_date' => today()]);
        Task::factory()->create(['title' => 'Other task due today', 'due_date' => today(), 'priority' => 2]);

        $this->get(route('tasks.index', ['view' => 'today', 'project' => $project->id]))
            ->assertOk()
            ->assertSee('Project task due today')
            ->assertDontSee('Other task due today');
    }

    public function test_new_tasks_from_the_today_tab_default_to_today(): void
    {
        $this->freezeTime();

        $this->get(route('tasks.index', ['view' => 'today']))
            ->assertSee('name="due_date" type="date" value="'.today()->toDateString().'"', false);

        $this->get(route('tasks.index'))
            ->assertSee('name="due_date" type="date" value=""', false);
    }

    public function test_load_more_keeps_the_view(): void
    {
        $this->freezeTime();
        Task::factory()
            ->count(TaskController::PER_PAGE + 1)
            ->sequence(fn ($sequence) => ['priority' => $sequence->index + 1])
            ->create(['due_date' => today()]);

        $this->get(route('tasks.index', ['view' => 'today']))
            ->assertSee('data-next-url="'.e(route('tasks.index', ['view' => 'today'])).'"', false);
    }
}
