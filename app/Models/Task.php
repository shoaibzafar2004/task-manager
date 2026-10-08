<?php

namespace App\Models;

use App\Services\Checklist;
use App\Services\MarkdownRenderer;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['project_id', 'title', 'info', 'due_date', 'priority', 'completed_at'])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class)->orderBy('name');
    }

    /**
     * The details rendered from Markdown. Safe to print unescaped.
     */
    public function infoHtml(): string
    {
        return app(MarkdownRenderer::class)->toHtml($this->info);
    }

    /**
     * The details as one line of plain text, for previews on cards.
     */
    public function infoExcerpt(): string
    {
        return app(MarkdownRenderer::class)->toPlainText($this->info);
    }

    /**
     * How many checklist entries in the details are ticked.
     *
     * @return array{done: int, total: int}
     */
    public function checklistProgress(): array
    {
        return app(Checklist::class)->progress($this->info);
    }

    /**
     * Identifies the current details text, so a checklist tick can't land on a stale index.
     */
    public function checklistVersion(): string
    {
        return hash('xxh128', (string) $this->info);
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * How urgent the due date is, or null when the task has no due date.
     *
     * @return 'overdue'|'today'|'soon'|'later'|null
     */
    public function dueStatus(): ?string
    {
        if ($this->due_date === null) {
            return null;
        }

        $daysLeft = (int) today()->diffInDays($this->due_date, false);

        return match (true) {
            $daysLeft < 0 => 'overdue',
            $daysLeft === 0 => 'today',
            $daysLeft <= 2 => 'soon',
            default => 'later',
        };
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull('completed_at');
    }

    #[Scope]
    protected function completed(Builder $query): void
    {
        $query->whereNotNull('completed_at');
    }

    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $query->when($term, function (Builder $q) use ($term) {
            $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';

            $q->where(fn (Builder $q) => $q
                ->whereRaw("title LIKE ? ESCAPE '!'", [$pattern])
                ->orWhereRaw("info LIKE ? ESCAPE '!'", [$pattern]));
        });
    }

    /**
     * Narrow to a due-date view: "today" (overdue or due today) or "upcoming" (the next 7 days).
     */
    #[Scope]
    protected function dueIn(Builder $query, ?string $view): void
    {
        match ($view) {
            'today' => $query->whereDate('due_date', '<=', today()),
            'upcoming' => $query->whereDate('due_date', '>', today())->whereDate('due_date', '<=', today()->addDays(7)),
            default => null,
        };
    }

    #[Scope]
    protected function withLabel(Builder $query, ?int $labelId): void
    {
        $query->when($labelId, fn (Builder $q) => $q->whereHas('labels', fn (Builder $q) => $q->whereKey($labelId)));
    }

    #[Scope]
    protected function forProject(Builder $query, ?int $projectId): void
    {
        $query->when($projectId, fn (Builder $q) => $q->where('project_id', $projectId));
    }
}
