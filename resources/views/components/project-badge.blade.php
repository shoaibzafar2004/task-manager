@props(['project'])

@if ($project)
    <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium"
          style="background-color: {{ $project->color }}1f; color: {{ $project->color }}">
        <span class="size-1.5 rounded-full" style="background-color: {{ $project->color }}"></span>
        {{ $project->name }}
    </span>
@else
    <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500 dark:bg-slate-800 dark:text-slate-400">No project</span>
@endif
