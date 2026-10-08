@props(['view' => null, 'counts', 'params' => []])

{{-- "All / Today / Upcoming" switcher on the task list; keeps the current filters --}}
<nav aria-label="Due date views" class="mb-4 flex gap-1 rounded-lg bg-slate-100 p-1 text-sm font-medium sm:w-fit dark:bg-slate-800">
    @foreach ([null => 'All', 'today' => 'Today', 'upcoming' => 'Upcoming'] as $key => $label)
        @php($key = $key ?: null)
        <a href="{{ route('tasks.index', [...$params, 'view' => $key]) }}" @if ($view === $key) aria-current="page" @endif
           @class([
               'flex flex-1 items-center justify-center gap-2 rounded-md px-3 py-1.5 transition sm:flex-none',
               'bg-white text-slate-900 shadow-sm dark:bg-slate-700 dark:text-white' => $view === $key,
               'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200' => $view !== $key,
           ])>
            {{ $label }}
            <span @class([
                'rounded-full px-1.5 text-xs tabular-nums',
                'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300' => $key === 'today' && $counts['overdue'] > 0,
                'bg-slate-200/70 text-slate-500 dark:bg-slate-600/60 dark:text-slate-300' => ! ($key === 'today' && $counts['overdue'] > 0),
            ]) @if ($key === 'today' && $counts['overdue'] > 0) title="{{ $counts['overdue'] }} overdue" @endif>{{ $counts[$key ?? 'all'] }}</span>
        </a>
    @endforeach
</nav>
