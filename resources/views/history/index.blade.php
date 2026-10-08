<x-layouts.app title="History">
    <div class="mb-4">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">History</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Completed and deleted tasks.</p>
    </div>

    <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <x-search-box route="history" :search="$search" :params="['tab' => $tab, 'project' => $projectId, 'label' => $labelId]" />
        <div class="grid grid-cols-2 gap-2">
            <x-filter-select :options="$projects" :selected="$projectId" param="project" all-text="All projects" route="history" :params="['tab' => $tab, 'label' => $labelId, 'q' => $search]" />
            <x-filter-select :options="$labels" :selected="$labelId" param="label" all-text="All labels" route="history" :params="['tab' => $tab, 'project' => $projectId, 'q' => $search]" />
        </div>
    </div>

    <div class="mb-4 flex gap-1 border-b border-slate-200 text-sm font-medium dark:border-slate-800">
        @foreach (['completed' => 'Completed', 'deleted' => 'Deleted'] as $key => $label)
            <a href="{{ route('history', ['tab' => $key, 'project' => $projectId, 'label' => $labelId, 'q' => $search]) }}"
               @class([
                   '-mb-px flex items-center gap-2 border-b-2 px-3 py-2 transition',
                   'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' => $tab === $key,
                   'border-transparent text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200' => $tab !== $key,
               ])>
                {{ $label }}
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500 dark:bg-slate-800 dark:text-slate-400">{{ $counts[$key] }}</span>
            </a>
        @endforeach
    </div>

    <ul id="history-list" class="space-y-2">
        @include('history.partials.items')
    </ul>

    @if ($tasks->isEmpty())
        <div class="rounded-xl border border-dashed border-slate-300 bg-white/50 px-6 py-14 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-400">
            {{ $search ? "No tasks match “{$search}”." : 'Nothing here yet.' }}
        </div>
    @endif

    <x-load-more list="history-list" :next-url="$tasks->nextPageUrl()" />
</x-layouts.app>
