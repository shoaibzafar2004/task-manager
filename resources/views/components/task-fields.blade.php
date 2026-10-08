@props(['projects', 'labels', 'task' => null, 'projectId' => null, 'labelId' => null, 'maxPriority' => null])

@php($input = 'w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm transition placeholder:text-slate-400 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 focus:outline-none dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:ring-indigo-500/20')
@php($label = 'mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300')
@php($hint = 'font-normal text-slate-400 dark:text-slate-500')
@php($error = 'mt-1 text-xs text-rose-600 dark:text-rose-400')
@php($selectedLabels = array_map('intval', old('title') !== null ? old('labels', []) : ($task?->labels->modelKeys() ?? array_filter([$labelId]))))

<div class="space-y-4">
    <div>
        <label for="title" class="{{ $label }}">Title</label>
        <input id="title" name="title" type="text" required maxlength="255" autocomplete="off"
               value="{{ old('title', $task?->title) }}" placeholder="What needs to be done?" class="{{ $input }}">
        @error('title') <p class="{{ $error }}">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="info" class="{{ $label }}">Details <span class="{{ $hint }}">(optional)</span></label>
        <textarea id="info" name="info" rows="3" placeholder="Add more info…" class="{{ $input }}">{{ old('info', $task?->info) }}</textarea>
        @error('info') <p class="{{ $error }}">{{ $message }}</p> @enderror
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div>
            <label for="project_id" class="{{ $label }}">Project</label>
            <select id="project_id" name="project_id" class="{{ $input }}">
                <option value="">No project</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" @selected((int) old('project_id', $task?->project_id ?? $projectId) === $project->id)>{{ $project->name }}</option>
                @endforeach
            </select>
            @error('project_id') <p class="{{ $error }}">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="due_date" class="{{ $label }}">Due date <span class="{{ $hint }}">(optional)</span></label>
            <input id="due_date" name="due_date" type="date" value="{{ old('due_date', $task?->due_date?->toDateString()) }}" class="{{ $input }}">
            @error('due_date') <p class="{{ $error }}">{{ $message }}</p> @enderror
        </div>

        @if (! $task?->isCompleted())
            <div>
                <label for="priority" class="{{ $label }}">Priority <span class="{{ $hint }}">(1 = highest)</span></label>
                <input id="priority" name="priority" type="number" min="1" @if ($maxPriority) max="{{ $maxPriority }}" @endif
                       value="{{ old('priority', $task?->priority) }}" placeholder="Bottom of list" class="{{ $input }}">
                @error('priority') <p class="{{ $error }}">{{ $message }}</p> @enderror
            </div>
        @endif
    </div>

    <fieldset>
        <legend class="{{ $label }}">Labels <span class="{{ $hint }}">(optional)</span></legend>
        @if ($labels->isEmpty())
            <p class="text-sm text-slate-400 dark:text-slate-500">No labels yet. Create one with the “+ Label” button.</p>
        @else
            <div class="flex flex-wrap gap-2">
                @foreach ($labels as $option)
                    <label class="label-toggle" style="--label: {{ $option->color }}">
                        <input type="checkbox" name="labels[]" value="{{ $option->id }}" class="sr-only" @checked(in_array($option->id, $selectedLabels, true))>
                        <svg class="label-toggle-check size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        {{ $option->name }}
                    </label>
                @endforeach
            </div>
        @endif
        @error('labels') <p class="{{ $error }}">{{ $message }}</p> @enderror
        @error('labels.*') <p class="{{ $error }}">{{ $message }}</p> @enderror
    </fieldset>
</div>
