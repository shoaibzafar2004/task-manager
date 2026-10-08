<x-layouts.app title="Tasks">
    @php($currentProject = $projects->firstWhere('id', $projectId))
    @php($currentLabel = $labels->firstWhere('id', $labelId))

    <div x-data="{ showForm: @js($errors->any()), showProject: @js($errors->project->any()), showLabel: @js($errors->label->any()) }">
        {{-- Header --}}
        <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="flex flex-wrap items-center gap-2 text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">
                    {{ $currentProject?->name ?? 'All tasks' }}
                    @if ($currentLabel) <x-label-chip :label="$currentLabel" /> @endif
                </h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    <span data-task-count>{{ $total }}</span> open {{ Str::plural('task', $total) }}@if ($search) matching “{{ $search }}”@endif · drag to reprioritise
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" @click="showProject = true"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Project
                </button>

                <button type="button" @click="showLabel = true"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Label
                </button>

                <button type="button" @click="showForm = ! showForm; $nextTick(() => showForm && $refs.newTask.querySelector('#title').focus())"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-medium text-white shadow-sm shadow-indigo-600/30 transition hover:bg-indigo-500">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    New task
                </button>
            </div>
        </div>

        {{-- Toolbar --}}
        <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <x-search-box route="tasks.index" :search="$search" :params="['project' => $projectId, 'label' => $labelId]" />
            <div class="grid grid-cols-2 gap-2">
                <x-filter-select :options="$projects" :selected="$projectId" param="project" all-text="All projects" route="tasks.index" :params="['label' => $labelId, 'q' => $search]" />
                <x-filter-select :options="$labels" :selected="$labelId" param="label" all-text="All labels" route="tasks.index" :params="['project' => $projectId, 'q' => $search]" />
            </div>
        </div>

        {{-- New task --}}
        <div x-show="showForm" x-collapse x-cloak x-ref="newTask">
            <form method="POST" action="{{ route('tasks.store') }}" class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                @csrf
                <x-task-fields :projects="$projects" :labels="$labels" :project-id="$projectId" :label-id="$labelId" />
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" @click="showForm = false" class="rounded-lg px-3.5 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">Cancel</button>
                    <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-500">Add task</button>
                </div>
            </form>
        </div>

        {{-- Task list --}}
        <ul id="task-list" data-reorder-url="{{ route('tasks.reorder') }}" class="space-y-2.5">
            @include('tasks.partials.cards')
        </ul>

        <x-load-more list="task-list" :next-url="$nextUrl" />

        {{-- Empty state (also revealed by JS when the last task is completed) --}}
        <div id="empty-state" @class(['rounded-xl border border-dashed border-slate-300 bg-white/50 px-6 py-14 text-center dark:border-slate-700 dark:bg-slate-900/50', 'hidden' => $tasks->isNotEmpty()])>
            @if ($search)
                <p class="font-medium text-slate-700 dark:text-slate-200">No tasks match “{{ $search }}”</p>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><a href="{{ route('tasks.index', ['project' => $projectId]) }}" class="text-indigo-600 hover:underline dark:text-indigo-400">Clear the search</a> to see all open tasks.</p>
            @else
                <div class="mx-auto mb-3 grid size-12 place-items-center rounded-full bg-emerald-50 text-emerald-500 dark:bg-emerald-500/10">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                </div>
                <p class="font-medium text-slate-700 dark:text-slate-200">All clear!</p>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">No open tasks here. Add one or check the <a href="{{ route('history', ['project' => $projectId]) }}" class="text-indigo-600 hover:underline dark:text-indigo-400">history</a>.</p>
            @endif
        </div>

        @if ($currentProject || $currentLabel)
            <div class="mt-8 flex justify-center gap-6">
                @if ($currentProject)
                    <form method="POST" action="{{ route('projects.destroy', $currentProject) }}"
                          data-confirm-title="Delete project?"
                          data-confirm="“{{ $currentProject->name }}” will be deleted. Its tasks are kept without a project. This can’t be undone."
                          data-confirm-button="Delete project">
                        @csrf @method('DELETE')
                        <button class="text-xs text-slate-400 transition hover:text-rose-600 dark:text-slate-500 dark:hover:text-rose-400">Delete this project</button>
                    </form>
                @endif
                @if ($currentLabel)
                    <form method="POST" action="{{ route('labels.destroy', $currentLabel) }}"
                          data-confirm-title="Delete label?"
                          data-confirm="“{{ $currentLabel->name }}” will be removed from all its tasks. The tasks themselves are kept. This can’t be undone."
                          data-confirm-button="Delete label">
                        @csrf @method('DELETE')
                        <button class="text-xs text-slate-400 transition hover:text-rose-600 dark:text-slate-500 dark:hover:text-rose-400">Delete this label</button>
                    </form>
                @endif
            </div>
        @endif

        <x-create-modal show="showProject" title="New project" :action="route('projects.store')" bag="project" button="Create project" />
        <x-create-modal show="showLabel" title="New label" :action="route('labels.store')" bag="label" button="Create label" :maxlength="50" default-color="#0ea5e9" />
    </div>
</x-layouts.app>
