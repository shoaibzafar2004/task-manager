<x-layouts.app :title="$task->title">
    <a href="{{ $backUrl }}" class="mb-4 inline-flex items-center gap-1 text-sm text-slate-500 transition hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200">
        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
        {{ $backLabel }}
    </a>

    @include('tasks.partials.detail')
</x-layouts.app>
