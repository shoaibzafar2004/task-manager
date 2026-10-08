<x-layouts.app title="History">
    <div class="mb-4">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">History</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Completed and deleted tasks.</p>
    </div>

    <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <x-search-box route="history" :search="$search" :params="['tab' => $tab, 'project' => $projectId]" />
        <x-project-filter :projects="$projects" :project-id="$projectId" route="history" :params="['tab' => $tab, 'q' => $search]" />
    </div>

    <div class="mb-4 flex gap-1 border-b border-slate-200 text-sm font-medium dark:border-slate-800">
        @foreach (['completed' => 'Completed', 'deleted' => 'Deleted'] as $key => $label)
            <a href="{{ route('history', ['tab' => $key, 'project' => $projectId, 'q' => $search]) }}"
               @class([
                   '-mb-px flex items-center gap-2 border-b-2 px-3 py-2 transition',
                   'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' => $tab === $key,
                   'border-transparent text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200' => $tab !== $key,
               ])>
                {{ $label }}
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500 dark:bg-slate-800 dark:text-slate-400">{{ $counts[$key] }}</span>
            </a>
        @endforeach
    </div>

    <ul class="space-y-2">
        @forelse ($tasks as $task)
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
                        <form method="POST" action="{{ route('tasks.destroy', $task) }}" onsubmit="return confirm('Delete this task?')">
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
        @empty
            <li class="rounded-xl border border-dashed border-slate-300 bg-white/50 px-6 py-14 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-400">
                {{ $search ? "No tasks match “{$search}”." : 'Nothing here yet.' }}
            </li>
        @endforelse
    </ul>

    <div class="mt-6">{{ $tasks->links() }}</div>
</x-layouts.app>
