<?php

namespace Tests\Feature;

use App\Models\Label;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LabelTest extends TestCase
{
    use RefreshDatabase;

    public function test_label_can_be_created(): void
    {
        $this->post(route('labels.store'), ['name' => 'bug', 'color' => '#ef4444'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('labels', ['name' => 'bug', 'color' => '#ef4444']);
    }

    public function test_label_validation_errors_use_their_own_bag(): void
    {
        Label::factory()->create(['name' => 'bug']);

        $this->post(route('labels.store'), ['name' => 'bug', 'color' => 'red'])
            ->assertSessionHasErrorsIn('label', ['name', 'color']);
    }

    public function test_task_labels_can_be_set_changed_and_cleared(): void
    {
        [$bug, $feature, $waiting] = Label::factory()->count(3)->create();

        $this->post(route('tasks.store'), ['title' => 'Fix crash', 'labels' => [$bug->id, $feature->id]])
            ->assertSessionHasNoErrors();
        $task = Task::firstWhere('title', 'Fix crash');
        $this->assertEqualsCanonicalizing([$bug->id, $feature->id], $task->labels->modelKeys());

        $this->put(route('tasks.update', $task), ['title' => 'Fix crash', 'labels' => [$waiting->id]]);
        $this->assertSame([$waiting->id], $task->fresh()->labels->modelKeys());

        // Unticking every chip sends no `labels` field at all.
        $this->put(route('tasks.update', $task), ['title' => 'Fix crash']);
        $this->assertEmpty($task->fresh()->labels);
    }

    public function test_unknown_labels_are_rejected(): void
    {
        $this->post(route('tasks.store'), ['title' => 'Fix crash', 'labels' => [999]])
            ->assertSessionHasErrors('labels.0');

        $this->assertDatabaseMissing('tasks', ['title' => 'Fix crash']);
    }

    public function test_index_filters_by_label_alone_and_with_a_project(): void
    {
        $bug = Label::factory()->create(['name' => 'bug']);
        $project = Project::factory()->create();
        Task::factory()->hasAttached($bug)->create(['title' => 'Bug in project', 'project_id' => $project->id]);
        Task::factory()->hasAttached($bug)->create(['title' => 'Bug elsewhere', 'priority' => 2]);
        Task::factory()->create(['title' => 'Not a bug', 'project_id' => $project->id, 'priority' => 3]);

        $this->get(route('tasks.index', ['label' => $bug->id]))
            ->assertOk()
            ->assertSee('Bug in project')
            ->assertSee('Bug elsewhere')
            ->assertDontSee('Not a bug');

        $this->get(route('tasks.index', ['label' => $bug->id, 'project' => $project->id]))
            ->assertOk()
            ->assertSee('Bug in project')
            ->assertDontSee('Bug elsewhere')
            ->assertDontSee('Not a bug');
    }

    public function test_history_filters_by_label(): void
    {
        $bug = Label::factory()->create();
        Task::factory()->completed()->hasAttached($bug)->create(['title' => 'Fixed crash']);
        Task::factory()->completed()->create(['title' => 'Wrote docs']);

        $this->get(route('history', ['label' => $bug->id]))
            ->assertOk()
            ->assertSee('Fixed crash')
            ->assertDontSee('Wrote docs');
    }

    public function test_deleting_a_label_keeps_its_tasks(): void
    {
        $label = Label::factory()->create();
        $task = Task::factory()->hasAttached($label)->create();

        $this->delete(route('labels.destroy', $label))->assertRedirect(route('tasks.index'));

        $this->assertModelMissing($label);
        $this->assertModelExists($task);
        $this->assertEmpty($task->fresh()->labels);
    }
}
