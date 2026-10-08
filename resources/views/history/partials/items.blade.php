@foreach ($tasks as $task)
    <li class="flex items-start gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        @if ($tab === 'completed')
            <span class="mt-0.5 grid size-5 shrink-0 place-items-center rounded-full bg-emerald-500 text-white">
                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
            </span>
        @else
            <span class="mt-0.5 grid size-5 shrink-0 place-items-center rounded-full bg-slate-200 text-slate-500 dark:bg-slate-700 dark:text-slate-400">
                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </span>
        @endif

        <div class="min-w-0 flex-1">
            <h2 @class(['font-medium wrap-break-word text-slate-500 dark:text-slate-400', 'line-through decoration-slate-300 dark:decoration-slate-600' => $tab === 'completed'])>{{ $task->title }}</h2>
            @if ($task->info)
                <p class="mt-1 line-clamp-2 text-sm whitespace-pre-line text-slate-400 dark:text-slate-500">{{ $task->info }}</p>
            @endif
            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-slate-400 dark:text-slate-500">
                <x-project-badge :project="$task->project" />
                <x-due-badge :task="$task" />
                @foreach ($task->labels as $taskLabel)
                    <x-label-chip :label="$taskLabel" />
                @endforeach
                <span>Created {{ $task->created_at->format('M j, Y') }}</span>
                @if ($tab === 'completed')
                    <span class="text-emerald-600 dark:text-emerald-400">· Completed {{ $task->completed_at->diffForHumans() }}</span>
                @else
                    <span class="text-rose-500 dark:text-rose-400">· Deleted {{ $task->deleted_at->diffForHumans() }}</span>
                @endif
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-1">
            @if ($tab === 'completed')
                <button type="button" data-reopen-url="{{ route('tasks.toggle', $task) }}"
                        class="reopen-btn rounded-md px-2.5 py-1.5 text-xs font-medium text-slate-500 transition hover:bg-indigo-50 hover:text-indigo-600 dark:text-slate-400 dark:hover:bg-indigo-500/10 dark:hover:text-indigo-400">Reopen</button>
                <form method="POST" action="{{ route('tasks.destroy', $task) }}"
                      data-confirm-title="Delete task?"
                      data-confirm="“{{ $task->title }}” will move to History → Deleted, where you can restore it."
                      data-confirm-button="Delete task">
                    @csrf @method('DELETE')
                    <button class="rounded-md px-2.5 py-1.5 text-xs font-medium text-slate-500 transition hover:bg-rose-50 hover:text-rose-600 dark:text-slate-400 dark:hover:bg-rose-500/10 dark:hover:text-rose-400">Delete</button>
                </form>
            @else
                <form method="POST" action="{{ route('tasks.restore', $task->id) }}">
                    @csrf @method('PATCH')
                    <button class="rounded-md px-2.5 py-1.5 text-xs font-medium text-slate-500 transition hover:bg-indigo-50 hover:text-indigo-600 dark:text-slate-400 dark:hover:bg-indigo-500/10 dark:hover:text-indigo-400">Restore</button>
                </form>
            @endif
        </div>
    </li>
@endforeach
