@php($modal ??= false)
@php($status = $task->trashed() ? 'deleted' : ($task->isCompleted() ? 'completed' : 'open'))
@php($button = 'inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-sm font-medium transition')

{{-- The task's details. Rendered as the task page and inside the popup on the list and History. --}}
<article data-task-id="{{ $task->id }}" data-task-status="{{ $status }}"
         @class(['relative', 'rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900' => ! $modal])>
    <header @class(['border-b border-slate-100 p-6 dark:border-slate-800', 'pr-14' => $modal])>
        @if ($modal)
            <button type="button" data-modal-close aria-label="Close"
                    class="absolute top-4 right-4 grid size-8 place-items-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        @endif
        <div class="mb-3 flex flex-wrap items-center gap-2 text-xs">
            @switch($status)
                @case('open')
                    <span class="prio-badge" data-priority="{{ $task->priority }}">#{{ $task->priority }}</span>
                    @break
                @case('completed')
                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                        <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        Completed
                    </span>
                    @break
                @case('deleted')
                    <span class="inline-flex items-center rounded-full bg-rose-100 px-2 py-0.5 font-medium text-rose-700 dark:bg-rose-500/15 dark:text-rose-300">Deleted</span>
                    @break
            @endswitch
            <x-project-badge :project="$task->project" />
            <x-due-badge :task="$task" />
            <x-recurrence-badge :task="$task" />
            @foreach ($task->labels as $taskLabel)
                <x-label-chip :label="$taskLabel" />
            @endforeach
        </div>

        <h1 @if ($modal) id="task-modal-title" @endif @class([
            'text-2xl font-semibold tracking-tight wrap-break-word',
            'text-slate-900 dark:text-white' => $status === 'open',
            'text-slate-500 line-through decoration-slate-300 dark:text-slate-400 dark:decoration-slate-600' => $status === 'completed',
            'text-slate-500 dark:text-slate-400' => $status === 'deleted',
        ])>{{ $task->title }}</h1>
    </header>

    <section class="p-6" aria-label="Details">
        @php($progress = $task->checklistProgress())
        @if ($progress['total'] > 0)
            <div class="mb-4 flex items-center gap-3 text-xs font-medium text-slate-500 dark:text-slate-400">
                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                    <div data-checklist-bar class="h-full rounded-full bg-emerald-500 transition-all duration-300"
                         style="width: {{ round($progress['done'] / $progress['total'] * 100) }}%"></div>
                </div>
                <span data-checklist-count class="tabular-nums">{{ $progress['done'] }} of {{ $progress['total'] }} done</span>
            </div>
        @endif

        @if ($task->info)
            {{-- Checklist boxes can be ticked here unless the task is deleted --}}
            <div class="prose-task"
                 @unless ($task->trashed())
                     data-checklist-url="{{ route('tasks.checklist', $task) }}"
                     data-checklist-version="{{ $task->checklistVersion() }}"
                 @endunless
            >{!! $task->infoHtml() !!}</div>
        @else
            <p class="text-sm text-slate-400 italic dark:text-slate-500">No details for this task.</p>
        @endif
    </section>

    <dl class="grid grid-cols-2 gap-x-6 gap-y-3 border-t border-slate-100 p-6 text-sm sm:grid-cols-4 dark:border-slate-800">
        @foreach (array_filter([
            'Created' => $task->created_at,
            'Updated' => $task->updated_at,
            'Due' => $task->due_date,
            'Completed' => $task->completed_at,
            'Deleted' => $task->deleted_at,
        ]) as $term => $date)
            <div>
                <dt class="text-xs font-medium tracking-wide text-slate-400 uppercase dark:text-slate-500">{{ $term }}</dt>
                <dd class="mt-0.5 text-slate-700 dark:text-slate-300" title="{{ $date }}">
                    {{ $term === 'Due' ? $date->format('M j, Y') : $date->format('M j, Y · H:i') }}
                </dd>
            </div>
        @endforeach
    </dl>

    <footer class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 p-4 dark:border-slate-800">
        @if ($status === 'deleted')
            <form method="POST" action="{{ route('tasks.restore', $task->id) }}" data-modal-action="restore">
                @csrf @method('PATCH')
                <button class="{{ $button }} bg-indigo-600 text-white shadow-sm hover:bg-indigo-500">Restore task</button>
            </form>
        @else
            <form method="POST" action="{{ route('tasks.destroy', $task) }}" class="mr-auto" data-modal-action="delete"
                  data-confirm-title="Delete task?"
                  data-confirm="“{{ $task->title }}” will move to History → Deleted, where you can restore it."
                  data-confirm-button="Delete task">
                @csrf @method('DELETE')
                <button class="{{ $button }} text-slate-500 hover:bg-rose-50 hover:text-rose-600 dark:text-slate-400 dark:hover:bg-rose-500/10 dark:hover:text-rose-400">Delete</button>
            </form>

            <a href="{{ route('tasks.edit', $task) }}" class="{{ $button }} border border-slate-200 text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Edit</a>

            <form method="POST" action="{{ route('tasks.toggle', $task) }}" data-modal-action="{{ $status === 'completed' ? 'reopen' : 'complete' }}">
                @csrf @method('PATCH')
                @if ($status === 'completed')
                    <button class="{{ $button }} bg-slate-900 text-white shadow-sm hover:bg-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600">Reopen task</button>
                @else
                    <button class="{{ $button }} bg-emerald-600 text-white shadow-sm shadow-emerald-600/30 hover:bg-emerald-500">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        Mark complete
                    </button>
                @endif
            </form>
        @endif
    </footer>
</article>
