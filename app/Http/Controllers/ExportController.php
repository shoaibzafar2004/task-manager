<?php

namespace App\Http\Controllers;

use App\Models\Label;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\LazyCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Downloads every project, label and task (open, completed and deleted) as JSON or CSV.
 * Tasks are read in chunks, so memory stays flat however many there are.
 */
class ExportController extends Controller
{
    public const CSV_COLUMNS = [
        'id', 'title', 'details', 'status', 'priority', 'project', 'labels',
        'due_date', 'created_at', 'updated_at', 'completed_at', 'deleted_at',
    ];

    public function json(): StreamedResponse
    {
        return response()->streamDownload(function () {
            echo '{"exported_at":'.json_encode(Date::now()->toIso8601String());
            echo ',"projects":'.json_encode(Project::orderBy('name')->get(['id', 'name', 'color']));
            echo ',"labels":'.json_encode(Label::orderBy('name')->get(['id', 'name', 'color']));
            echo ',"tasks":[';

            foreach ($this->tasks() as $i => $task) {
                echo ($i > 0 ? ',' : '').json_encode($this->row($task), JSON_UNESCAPED_UNICODE);
            }

            echo ']}';
        }, $this->filename('json'), ['Content-Type' => 'application/json']);
    }

    public function csv(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\u{FEFF}"); // BOM, so Excel reads the file as UTF-8
            fputcsv($out, self::CSV_COLUMNS, escape: '');

            foreach ($this->tasks() as $task) {
                $row = $this->row($task);
                $row['labels'] = implode(', ', $row['labels']);
                fputcsv($out, array_map($this->safeCell(...), $row), escape: '');
            }

            fclose($out);
        }, $this->filename('csv'), ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return LazyCollection<int, Task>
     */
    private function tasks()
    {
        return Task::withTrashed()->with(['project', 'labels'])->lazyById(200)->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Task $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'details' => $task->info,
            'status' => $task->trashed() ? 'deleted' : ($task->isCompleted() ? 'completed' : 'open'),
            'priority' => $task->trashed() || $task->isCompleted() ? null : $task->priority,
            'project' => $task->project?->name,
            'labels' => $task->labels->pluck('name')->all(),
            'due_date' => $task->due_date?->toDateString(),
            'created_at' => $task->created_at?->toIso8601String(),
            'updated_at' => $task->updated_at?->toIso8601String(),
            'completed_at' => $task->completed_at?->toIso8601String(),
            'deleted_at' => $task->deleted_at?->toIso8601String(),
        ];
    }

    /**
     * Spreadsheet apps run cells starting with = + - @ as formulas; prefix them so they stay text.
     */
    private function safeCell(mixed $value): mixed
    {
        return is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }

    private function filename(string $extension): string
    {
        return 'task-manager-'.Date::now()->format('Y-m-d').'.'.$extension;
    }
}
