@props(['projects', 'task' => null, 'projectId' => null, 'maxPriority' => null])

@php($input = 'w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm transition placeholder:text-slate-400 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 focus:outline-none')

<div class="space-y-4">
    <div>
        <label for="title" class="mb-1 block text-sm font-medium text-slate-700">Title</label>
        <input id="title" name="title" type="text" required maxlength="255" autocomplete="off"
               value="{{ old('title', $task?->title) }}" placeholder="What needs to be done?" class="{{ $input }}">
        @error('title') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="info" class="mb-1 block text-sm font-medium text-slate-700">Details <span class="font-normal text-slate-400">(optional)</span></label>
        <textarea id="info" name="info" rows="3" placeholder="Add more info…" class="{{ $input }}">{{ old('info', $task?->info) }}</textarea>
        @error('info') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="project_id" class="mb-1 block text-sm font-medium text-slate-700">Project</label>
            <select id="project_id" name="project_id" class="{{ $input }}">
                <option value="">No project</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" @selected((int) old('project_id', $task?->project_id ?? $projectId) === $project->id)>{{ $project->name }}</option>
                @endforeach
            </select>
            @error('project_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        @if (! $task?->isCompleted())
            <div>
                <label for="priority" class="mb-1 block text-sm font-medium text-slate-700">Priority <span class="font-normal text-slate-400">(1 = highest)</span></label>
                <input id="priority" name="priority" type="number" min="1" @if ($maxPriority) max="{{ $maxPriority }}" @endif
                       value="{{ old('priority', $task?->priority) }}" placeholder="Bottom of the list" class="{{ $input }}">
                @error('priority') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
        @endif
    </div>
</div>
