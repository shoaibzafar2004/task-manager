@props(['task'])

@php($progress = $task->checklistProgress())

@if ($progress['total'] > 0)
    @php($complete = $progress['done'] === $progress['total'])
    <span title="{{ $progress['done'] }} of {{ $progress['total'] }} checklist items done"
          @class([
              'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium tabular-nums',
              'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' => $complete,
              'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' => ! $complete,
          ])>
        <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
        {{ $progress['done'] }}/{{ $progress['total'] }}
    </span>
@endif
