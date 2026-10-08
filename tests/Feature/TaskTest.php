<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_active_tasks_by_priority(): void
    {
        Task::factory()->create(['title' => 'Second', 'priority' => 2]);
        Task::factory()->create(['title' => 'First', 'priority' => 1]);
        Task::factory()->completed()->create(['title' => 'Finished', 'priority' => 3]);

        $this->get(route('tasks.index'))
            ->assertOk()
            ->assertSeeInOrder(['First', 'Second'])
            ->assertDontSee('Finished');
    }

    public function test_index_filters_by_project(): void
    {
        $project = Project::factory()->create();
        Task::factory()->create(['title' => 'In project', 'project_id' => $project->id]);
        Task::factory()->create(['title' => 'Elsewhere', 'priority' => 2]);

        $this->get(route('tasks.index', ['project' => $project->id]))
            ->assertOk()
            ->assertSee('In project')
            ->assertDontSee('Elsewhere');
    }

    public function test_index_searches_title_and_info(): void
    {
        Task::factory()->create(['title' => 'Buy milk', 'info' => null]);
        Task::factory()->create(['title' => 'Call bank', 'info' => 'Ask about the milk card', 'priority' => 2]);
        Task::factory()->create(['title' => 'Walk dog', 'info' => null, 'priority' => 3]);

        $this->get(route('tasks.index', ['q' => 'milk']))
            ->assertOk()
            ->assertSee('Buy milk')
            ->assertSee('Call bank')
            ->assertDontSee('Walk dog');
    }

    public function test_search_treats_wildcards_literally(): void
    {
        Task::factory()->create(['title' => '100% done']);
        Task::factory()->create(['title' => 'Unrelated', 'priority' => 2]);

        $this->get(route('tasks.index', ['q' => '%']))
            ->assertOk()
            ->assertSee('100% done')
            ->assertDontSee('Unrelated');
    }

    public function test_history_can_be_searched(): void
    {
        Task::factory()->completed()->create(['title' => 'Paid invoice']);
        Task::factory()->completed()->create(['title' => 'Fixed bug']);

        $this->get(route('history', ['q' => 'invoice']))
            ->assertOk()
            ->assertSee('Paid invoice')
            ->assertDontSee('Fixed bug');
    }

    public function test_task_can_be_created(): void
    {
        $project = Project::factory()->create();

        $this->post(route('tasks.store'), ['title' => 'Write tests', 'info' => 'All of them', 'project_id' => $project->id])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tasks', ['title' => 'Write tests', 'project_id' => $project->id, 'priority' => 1]);
    }

    public function test_title_is_required(): void
    {
        $this->post(route('tasks.store'), ['title' => ''])->assertSessionHasErrors('title');
    }

    public function test_task_can_be_updated(): void
    {
        $task = Task::factory()->create(['title' => 'Old']);

        $this->get(route('tasks.edit', $task))->assertOk()->assertSee('Old');

        $this->put(route('tasks.update', $task), ['title' => 'New', 'info' => 'Details'])
            ->assertRedirect(route('tasks.index'));

        $this->assertSame('New', $task->fresh()->title);
    }

    public function test_history_shows_completed_and_deleted_tasks(): void
    {
        Task::factory()->completed()->create(['title' => 'Done one']);
        Task::factory()->create(['title' => 'Trashed one'])->delete();

        $this->get(route('history'))->assertOk()->assertSee('Done one')->assertDontSee('Trashed one');
        $this->get(route('history', ['tab' => 'deleted']))->assertOk()->assertSee('Trashed one')->assertDontSee('Done one');
    }

    public function test_project_can_be_created_and_deleted(): void
    {
        $this->post(route('projects.store'), ['name' => 'Launch', 'color' => '#10b981'])->assertRedirect();
        $project = Project::firstWhere('name', 'Launch');
        $task = Task::factory()->create(['project_id' => $project->id]);

        $this->post(route('projects.store'), ['name' => 'Launch', 'color' => '#10b981'])->assertSessionHasErrorsIn('project', 'name');

        $this->delete(route('projects.destroy', $project))->assertRedirect(route('tasks.index'));
        $this->assertModelMissing($project);
        $this->assertNull($task->fresh()->project_id);
    }
}
