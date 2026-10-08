@foreach ($tasks as $task)
    <li data-id="{{ $task->id }}" data-priority="{{ $task->priority }}"
        class="task-card group flex items-start gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-slate-300 hover:shadow dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700">
        <button type="button" class="drag-handle mt-0.5 cursor-grab touch-none text-slate-300 transition hover:text-slate-500 active:cursor-grabbing dark:text-slate-600 dark:hover:text-slate-400" aria-label="Drag to reorder">
            <svg class="size-5" viewBox="0 0 20 20" fill="currentColor"><path d="M7 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 6a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm-1.5 7.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM16 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm-1.5 7.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM16 16a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"/></svg>
        </button>

        <input type="checkbox" data-toggle-url="{{ route('tasks.toggle', $task) }}" aria-label="Mark “{{ $task->title }}” complete"
               class="task-check mt-0.5 size-5 shrink-0 cursor-pointer rounded-full border-2 border-slate-300 transition hover:border-emerald-400 dark:border-slate-600">

        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <span class="prio-badge" data-priority="{{ $task->priority }}">#{{ $task->priority }}</span>
                <h2 class="task-title font-medium wrap-break-word text-slate-900 dark:text-slate-100">{{ $task->title }}</h2>
            </div>
            @if ($task->info)
                <p class="mt-1 line-clamp-2 text-sm whitespace-pre-line text-slate-500 dark:text-slate-400">{{ $task->info }}</p>
            @endif
            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-slate-400 dark:text-slate-500">
                <x-project-badge :project="$task->project" />
                <x-due-badge :task="$task" />
                @foreach ($task->labels as $taskLabel)
                    <x-label-chip :label="$taskLabel" />
                @endforeach
                <span title="{{ $task->created_at }}">Created {{ $task->created_at->diffForHumans() }}</span>
                @if ($task->updated_at->gt($task->created_at))
                    <span title="{{ $task->updated_at }}">· Updated {{ $task->updated_at->diffForHumans() }}</span>
                @endif
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-1 opacity-100 transition sm:opacity-0 sm:group-hover:opacity-100 sm:focus-within:opacity-100">
            <a href="{{ route('tasks.edit', $task) }}" class="rounded-md p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-indigo-600 dark:hover:bg-slate-800 dark:hover:text-indigo-400" aria-label="Edit task">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
            </a>
            <form method="POST" action="{{ route('tasks.destroy', $task) }}"
                  data-confirm-title="Delete task?"
                  data-confirm="“{{ $task->title }}” will move to History → Deleted, where you can restore it."
                  data-confirm-button="Delete task">
                @csrf @method('DELETE')
                <button class="rounded-md p-1.5 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-500/10 dark:hover:text-rose-400" aria-label="Delete task">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                </button>
            </form>
        </div>
    </li>
@endforeach
