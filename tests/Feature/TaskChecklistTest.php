<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Services\Checklist;
use App\Services\MarkdownRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TaskChecklistTest extends TestCase
{
    use RefreshDatabase;

    private const LIST = "Steps:\n\n- [ ] Draft\n- [x] Review\n- [ ] Publish\n";

    /**
     * @return array<string, array{0: string}>
     */
    public static function documents(): array
    {
        return [
            'plain list' => [self::LIST],
            'fenced code is not a checklist' => ["- [ ] real\n```\n- [ ] in code\n```\n- [x] real too\n"],
            'tilde fence closed only by a matching fence' => ["~~~~\n- [ ] code\n```\n- [ ] still code\n~~~~\n* [x] after\n"],
            'quotes, numbers and nesting' => ["> - [x] quoted\n1. [ ] numbered\n   - [X] nested\n"],
            'empty boxes stay text' => ["- [ ]\n- [ ]   \n- [ ]no space\n- [ ]*emphasis*\n"],
        ];
    }

    #[DataProvider('documents')]
    public function test_checklist_entries_line_up_with_rendered_checkboxes(string $markdown): void
    {
        $rendered = substr_count(app(MarkdownRenderer::class)->toHtml($markdown), '<input');

        $this->assertSame($rendered, app(Checklist::class)->progress($markdown)['total']);
    }

    public function test_ticking_an_item_updates_the_details(): void
    {
        $task = Task::factory()->create(['info' => self::LIST]);

        $this->patchJson(route('tasks.checklist', $task), ['index' => 2, 'checked' => true, 'version' => $task->checklistVersion()])
            ->assertOk()
            ->assertJson(['done' => 2, 'total' => 3, 'version' => $task->fresh()->checklistVersion()]);

        $this->assertSame("Steps:\n\n- [ ] Draft\n- [x] Review\n- [x] Publish\n", $task->fresh()->info);
    }

    public function test_unticking_an_item_updates_the_details(): void
    {
        $task = Task::factory()->create(['info' => self::LIST]);

        $this->patchJson(route('tasks.checklist', $task), ['index' => 1, 'checked' => false, 'version' => $task->checklistVersion()])
            ->assertOk()
            ->assertJson(['done' => 0, 'total' => 3]);

        $this->assertStringContainsString('- [ ] Review', $task->fresh()->info);
    }

    public function test_stale_version_is_refused(): void
    {
        $task = Task::factory()->create(['info' => self::LIST]);
        $staleVersion = $task->checklistVersion();
        $task->update(['info' => "- [ ] New first item\n".self::LIST]);

        $this->patchJson(route('tasks.checklist', $task), ['index' => 0, 'checked' => true, 'version' => $staleVersion])
            ->assertConflict();

        $this->assertStringStartsWith('- [ ] New first item', $task->fresh()->info);
    }

    public function test_index_out_of_range_is_rejected(): void
    {
        $task = Task::factory()->create(['info' => self::LIST]);

        $this->patchJson(route('tasks.checklist', $task), ['index' => 3, 'checked' => true, 'version' => $task->checklistVersion()])
            ->assertUnprocessable();

        $this->assertSame(self::LIST, $task->fresh()->info);
    }

    public function test_deleted_tasks_cannot_be_ticked(): void
    {
        $task = Task::factory()->create(['info' => self::LIST]);
        $task->delete();

        $this->patchJson(route('tasks.checklist', $task->id), ['index' => 0, 'checked' => true, 'version' => $task->checklistVersion()])
            ->assertNotFound();
    }

    public function test_task_page_enables_ticking_only_for_tasks_that_are_not_deleted(): void
    {
        $task = Task::factory()->create(['info' => self::LIST]);

        $this->get(route('tasks.show', $task))
            ->assertSee('1 of 3 done')
            ->assertSee(route('tasks.checklist', $task));

        $task->delete();

        $this->get(route('tasks.show', $task->id))->assertDontSee(route('tasks.checklist', $task->id));
    }

    public function test_cards_show_checklist_progress(): void
    {
        Task::factory()->create(['info' => self::LIST]);

        $this->get(route('tasks.index'))->assertSee('1/3');
    }
}
