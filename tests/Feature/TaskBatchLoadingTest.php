<?php

namespace Tests\Feature;

use App\Http\Controllers\HistoryController;
use App\Http\Controllers\TaskController;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class TaskBatchLoadingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create open tasks with priorities 1..$count.
     *
     * @return Collection<int, Task>
     */
    private function openTasks(int $count, array $attributes = []): Collection
    {
        return Task::factory()
            ->count($count)
            ->sequence(fn ($sequence) => ['priority' => $sequence->index + 1])
            ->create($attributes);
    }

    /**
     * @return array<int, int>
     */
    private function cardIds(string $html): array
    {
        preg_match_all('/<li data-id="(\d+)"/', $html, $matches);

        return array_map('intval', $matches[1]);
    }

    private function nextUrl(TestResponse $response): ?string
    {
        preg_match('/data-next-url="([^"]*)"/', $response->getContent(), $match);

        return html_entity_decode($match[1]) ?: null;
    }

    public function test_tasks_load_in_batches_without_gaps_or_duplicates(): void
    {
        $tasks = $this->openTasks(TaskController::PER_PAGE + 5);

        $page = $this->get(route('tasks.index'))->assertOk()->assertSee('<span data-task-count>105</span>', false);
        $firstBatch = $this->cardIds($page->getContent());
        $this->assertSame($tasks->take(TaskController::PER_PAGE)->pluck('id')->all(), $firstBatch);

        $next = $this->getJson($this->nextUrl($page).'?after='.TaskController::PER_PAGE)
            ->assertOk()
            ->assertJsonPath('next_url', null);

        $this->assertSame($tasks->skip(TaskController::PER_PAGE)->pluck('id')->values()->all(), $this->cardIds($next->json('html')));
    }

    public function test_completing_a_task_between_batches_does_not_skip_the_next_one(): void
    {
        $tasks = $this->openTasks(TaskController::PER_PAGE + 5);
        $page = $this->get(route('tasks.index'));

        // Completing #50 shifts every later task up by one; the client sends its renumbered last priority.
        $this->patchJson(route('tasks.toggle', $tasks[49]))->assertOk();
        $next = $this->getJson($this->nextUrl($page).'?after='.(TaskController::PER_PAGE - 1));

        $this->assertSame($tasks->skip(TaskController::PER_PAGE)->pluck('id')->values()->all(), $this->cardIds($next->json('html')));
    }

    public function test_batches_respect_the_project_filter(): void
    {
        $project = Project::factory()->create();
        $other = Task::factory()->create(['priority' => 1]);
        $inProject = Task::factory()
            ->count(TaskController::PER_PAGE + 1)
            ->sequence(fn ($sequence) => ['priority' => $sequence->index + 2])
            ->create(['project_id' => $project->id]);

        $page = $this->get(route('tasks.index', ['project' => $project->id]));
        $this->assertNotContains($other->id, $this->cardIds($page->getContent()));
        $this->assertCount(TaskController::PER_PAGE, $this->cardIds($page->getContent()));

        $lastLoaded = $inProject[TaskController::PER_PAGE - 1]->priority;
        $next = $this->getJson($this->nextUrl($page).'&after='.$lastLoaded);

        $this->assertSame([$inProject->last()->id], $this->cardIds($next->json('html')));
    }

    public function test_short_lists_have_no_load_more(): void
    {
        $this->openTasks(3);

        $this->assertNull($this->nextUrl($this->get(route('tasks.index'))));
    }

    public function test_history_loads_in_batches_even_after_reopening_a_task(): void
    {
        $completed = Task::factory()
            ->completed()
            ->count(HistoryController::PER_PAGE + 5)
            ->sequence(fn ($sequence) => ['completed_at' => now()->subMinutes($sequence->index)])
            ->create();

        $page = $this->get(route('history'));
        $this->assertSame($completed->take(HistoryController::PER_PAGE)->pluck('id')->all(), $this->historyIds($page->getContent()));

        $this->patchJson(route('tasks.toggle', $completed[3]))->assertOk();
        $next = $this->getJson($this->nextUrl($page))->assertOk()->assertJsonPath('next_url', null);

        $this->assertSame($completed->skip(HistoryController::PER_PAGE)->pluck('id')->values()->all(), $this->historyIds($next->json('html')));
    }

    /**
     * @return array<int, int>
     */
    private function historyIds(string $html): array
    {
        preg_match_all('/data-reopen-url="[^"]*\/tasks\/(\d+)\/toggle"/', $html, $matches);

        return array_map('intval', $matches[1]);
    }
}
