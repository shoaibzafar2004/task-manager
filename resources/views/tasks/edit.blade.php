<x-layouts.app title="Edit task">
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('tasks.index') }}"
       class="mb-4 inline-flex items-center gap-1 text-sm text-slate-500 transition hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200">
        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
        Back
    </a>

    <form method="POST" action="{{ route('tasks.update', $task) }}" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        @csrf @method('PUT')
        {{-- Return to the task page after saving when that's where the edit started --}}
        @if (old('from', url()->previous() === route('tasks.show', $task) ? 'show' : null) === 'show')
            <input type="hidden" name="from" value="show">
        @endif

        <div class="mb-5 flex items-start justify-between gap-4">
            <h1 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-white">Edit task</h1>
            <div class="text-right text-xs text-slate-400 dark:text-slate-500">
                <div>Created {{ $task->created_at->format('M j, Y · H:i') }}</div>
                <div>Updated {{ $task->updated_at->diffForHumans() }}</div>
                @if ($task->isCompleted())
                    <div class="text-emerald-600 dark:text-emerald-400">Completed {{ $task->completed_at->format('M j, Y · H:i') }}</div>
                @endif
            </div>
        </div>

        <x-task-fields :projects="$projects" :labels="$labels" :task="$task" :max-priority="$maxPriority" />

        <div class="mt-6 flex justify-end gap-2">
            <a href="{{ route('tasks.index') }}" class="rounded-lg px-3.5 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">Cancel</a>
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-500">Save changes</button>
        </div>
    </form>
</x-layouts.app>
