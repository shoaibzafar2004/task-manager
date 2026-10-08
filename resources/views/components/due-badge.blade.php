@props(['task'])

@if ($task->due_date)
    @php($status = $task->isCompleted() ? 'later' : $task->dueStatus())
    <span title="Due {{ $task->due_date->format('l, M j, Y') }}"
          @class([
              'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium',
              'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300' => $status === 'overdue',
              'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300' => $status === 'today',
              'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300' => $status === 'soon',
              'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' => $status === 'later',
          ])>
        <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
        @switch($status)
            @case('overdue') Overdue · {{ $task->due_date->format('M j') }} @break
            @case('today') Due today @break
            @default {{ $task->due_date->isTomorrow() ? 'Due tomorrow' : 'Due '.$task->due_date->format($task->due_date->isCurrentYear() ? 'M j' : 'M j, Y') }}
        @endswitch
    </span>
@endif
