<?php

namespace App\Models;

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
