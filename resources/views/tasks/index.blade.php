<x-layouts.app title="Tasks">
    @php($currentProject = $projects->firstWhere('id', $projectId))

    <div x-data="{ showForm: {{ $errors->any() && ! $errors->has('name') && ! $errors->has('color') ? 'true' : 'false' }}, showProject: {{ $errors->has('name') || $errors->has('color') ? 'true' : 'false' }} }">
        {{-- Header --}}
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">{{ $currentProject?->name ?? 'All tasks' }}</h1>
                <p class="mt-1 text-sm text-slate-500">
                    <span data-task-count>{{ $tasks->count() }}</span> open {{ Str::plural('task', $tasks->count()) }} · drag to reprioritise
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <x-project-filter :projects="$projects" :project-id="$projectId" route="tasks.index" />

                <button type="button" @click="showProject = true"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Project
                </button>

                <button type="button" @click="showForm = ! showForm; $nextTick(() => showForm && $refs.newTask.querySelector('#title').focus())"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-medium text-white shadow-sm shadow-indigo-600/30 transition hover:bg-indigo-500">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    New task
                </button>
            </div>
        </div>

        {{-- New task --}}
        <div x-show="showForm" x-collapse x-cloak x-ref="newTask">
            <form method="POST" action="{{ route('tasks.store') }}" class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                @csrf
                <x-task-fields :projects="$projects" :project-id="$projectId" />
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" @click="showForm = false" class="rounded-lg px-3.5 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Cancel</button>
                    <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-500">Add task</button>
                </div>
            </form>
        </div>

        {{-- Task list --}}
        <ul id="task-list" data-reorder-url="{{ route('tasks.reorder') }}" class="space-y-2.5">
            @foreach ($tasks as $task)
                <li data-id="{{ $task->id }}" data-priority="{{ $task->priority }}"
                    class="task-card group flex items-start gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-slate-300 hover:shadow">
                    <button type="button" class="drag-handle mt-0.5 cursor-grab touch-none text-slate-300 transition hover:text-slate-500 active:cursor-grabbing" aria-label="Drag to reorder">
                        <svg class="size-5" viewBox="0 0 20 20" fill="currentColor"><path d="M7 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 6a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm-1.5 7.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM16 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm-1.5 7.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM16 16a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"/></svg>
                    </button>

                    <input type="checkbox" data-toggle-url="{{ route('tasks.toggle', $task) }}" aria-label="Mark “{{ $task->title }}” complete"
                           class="task-check mt-0.5 size-5 shrink-0 cursor-pointer rounded-full border-2 border-slate-300 accent-emerald-500 transition hover:border-emerald-400">

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="prio-badge" data-priority="{{ $task->priority }}">#{{ $task->priority }}</span>
                            <h2 class="task-title font-medium break-words text-slate-900">{{ $task->title }}</h2>
                        </div>
                        @if ($task->info)
                            <p class="mt-1 line-clamp-2 text-sm whitespace-pre-line text-slate-500">{{ $task->info }}</p>
                        @endif
                        <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-slate-400">
                            <x-project-badge :project="$task->project" />
                            <span title="{{ $task->created_at }}">Created {{ $task->created_at->diffForHumans() }}</span>
                            @if ($task->updated_at->gt($task->created_at))
                                <span title="{{ $task->updated_at }}">· Updated {{ $task->updated_at->diffForHumans() }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-1 opacity-100 transition sm:opacity-0 sm:group-hover:opacity-100 sm:focus-within:opacity-100">
                        <a href="{{ route('tasks.edit', $task) }}" class="rounded-md p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-indigo-600" aria-label="Edit task">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                        </a>
                        <form method="POST" action="{{ route('tasks.destroy', $task) }}" onsubmit="return confirm('Delete this task?')">
                            @csrf @method('DELETE')
                            <button class="rounded-md p-1.5 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600" aria-label="Delete task">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                            </button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>

        {{-- Empty state (also revealed by JS when the last task is completed) --}}
        <div id="empty-state" @class(['rounded-xl border border-dashed border-slate-300 bg-white/50 px-6 py-14 text-center', 'hidden' => $tasks->isNotEmpty()])>
            <div class="mx-auto mb-3 grid size-12 place-items-center rounded-full bg-emerald-50 text-emerald-500">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
            </div>
            <p class="font-medium text-slate-700">All clear!</p>
            <p class="mt-1 text-sm text-slate-500">No open tasks here. Add one or check the <a href="{{ route('history', ['project' => $projectId]) }}" class="text-indigo-600 hover:underline">history</a>.</p>
        </div>

        @if ($currentProject)
            <form method="POST" action="{{ route('projects.destroy', $currentProject) }}" class="mt-8 text-center"
                  onsubmit="return confirm('Delete project “{{ addslashes($currentProject->name) }}”? Its tasks will be kept without a project.')">
                @csrf @method('DELETE')
                <button class="text-xs text-slate-400 transition hover:text-rose-600">Delete this project</button>
            </form>
        @endif

        {{-- New project modal --}}
        <div x-show="showProject" x-cloak @keydown.escape.window="showProject = false"
             class="fixed inset-0 z-40 grid place-items-center bg-slate-900/40 p-4 backdrop-blur-sm"
             x-transition.opacity>
            <form method="POST" action="{{ route('projects.store') }}" @click.outside="showProject = false"
                  x-data="{ color: @js(old('color', '#6366f1')) }"
                  x-show="showProject" x-transition.scale.95
                  class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
                @csrf
                <h2 class="text-lg font-semibold text-slate-900">New project</h2>

                <label for="name" class="mt-4 mb-1 block text-sm font-medium text-slate-700">Name</label>
                <input id="name" name="name" required maxlength="100" value="{{ old('name') }}" x-effect="showProject && $nextTick(() => $el.focus())"
                       class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 focus:outline-none">
                @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror

                <span class="mt-4 mb-2 block text-sm font-medium text-slate-700">Color</span>
                <input type="hidden" name="color" :value="color">
                <div class="flex flex-wrap gap-2">
                    @foreach (['#6366f1', '#0ea5e9', '#10b981', '#84cc16', '#f59e0b', '#f97316', '#ef4444', '#ec4899', '#8b5cf6', '#64748b'] as $swatch)
                        <button type="button" @click="color = '{{ $swatch }}'" aria-label="Color {{ $swatch }}"
                                :class="color === '{{ $swatch }}' ? 'ring-2 ring-offset-2 ring-slate-400 scale-110' : ''"
                                class="size-7 rounded-full transition" style="background-color: {{ $swatch }}"></button>
                    @endforeach
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" @click="showProject = false" class="rounded-lg px-3.5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500">Create project</button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
