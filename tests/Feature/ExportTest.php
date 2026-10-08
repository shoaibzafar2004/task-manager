<?php

namespace Tests\Feature;

use App\Http\Controllers\ExportController;
use App\Models\Label;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{open: Task, completed: Task, deleted: Task}
     */
    private function sampleData(): array
    {
        $this->travelTo('2026-10-08 09:30:00');
        $project = Project::factory()->create(['name' => 'Home', 'color' => '#10b981']);
        $label = Label::factory()->create(['name' => 'errand', 'color' => '#f59e0b']);

        $open = Task::factory()->for($project)->hasAttached($label)->create([
            'title' => 'Buy paint', 'info' => '- [ ] white', 'due_date' => '2026-10-10', 'priority' => 1,
        ]);
        $completed = Task::factory()->completed()->create(['title' => 'Fix tap', 'info' => null, 'priority' => 2]);
        $deleted = Task::factory()->create(['title' => 'Old idea', 'info' => null, 'priority' => 3]);
        $deleted->delete();

        return compact('open', 'completed', 'deleted');
    }

    public function test_json_export_contains_everything(): void
    {
        ['open' => $open] = $this->sampleData();

        $response = $this->get(route('export.json'))
            ->assertOk()
            ->assertDownload('task-manager-2026-10-08.json');

        $data = json_decode($response->streamedContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame([['id' => $open->project_id, 'name' => 'Home', 'color' => '#10b981']], $data['projects']);
        $this->assertSame('errand', $data['labels'][0]['name']);
        $this->assertSame(['open', 'completed', 'deleted'], array_column($data['tasks'], 'status'));
        $this->assertSame([
            'id' => $open->id,
            'title' => 'Buy paint',
            'details' => '- [ ] white',
            'status' => 'open',
            'priority' => 1,
            'project' => 'Home',
            'labels' => ['errand'],
            'due_date' => '2026-10-10',
            'created_at' => '2026-10-08T09:30:00+00:00',
            'updated_at' => '2026-10-08T09:30:00+00:00',
            'completed_at' => null,
            'deleted_at' => null,
        ], $data['tasks'][0]);
        $this->assertNull($data['tasks'][1]['priority']);
    }

    public function test_csv_export_has_a_header_and_one_row_per_task(): void
    {
        $this->sampleData();

        $response = $this->get(route('export.csv'))
            ->assertOk()
            ->assertDownload('task-manager-2026-10-08.csv');

        $lines = array_map(
            fn (string $line) => str_getcsv($line, escape: ''),
            explode("\n", trim(ltrim($response->streamedContent(), "\u{FEFF}"))),
        );

        $this->assertSame(ExportController::CSV_COLUMNS, $lines[0]);
        $this->assertCount(4, $lines);
        $this->assertSame(['Buy paint', 'open', 'Home', 'errand'], [$lines[1][1], $lines[1][3], $lines[1][5], $lines[1][6]]);
        $this->assertSame('deleted', $lines[3][3]);
    }

    public function test_csv_cells_cannot_run_as_spreadsheet_formulas(): void
    {
        Task::factory()->create(['title' => '=HYPERLINK("http://evil.test","click")', 'info' => '+cmd', 'priority' => 1]);

        $content = $this->get(route('export.csv'))->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $content);
        $this->assertStringContainsString("'+cmd", $content);
    }

    public function test_history_page_links_to_both_exports(): void
    {
        $this->get(route('history'))
            ->assertOk()
            ->assertSee(route('export.json'))
            ->assertSee(route('export.csv'));
    }
}
