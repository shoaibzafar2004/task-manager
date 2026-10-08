@props(['route', 'search' => null, 'params' => []])

<form method="GET" action="{{ route($route) }}" role="search" class="relative">
    @foreach (array_filter($params, fn ($value) => $value !== null) as $name => $value)
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endforeach

    <svg class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
    <input type="search" name="q" value="{{ $search }}" placeholder="Search tasks…" aria-label="Search tasks"
           class="w-full rounded-lg border border-slate-200 bg-white py-2 pr-8 pl-9 text-sm shadow-sm transition placeholder:text-slate-400 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 focus:outline-none sm:w-52 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:focus:ring-indigo-500/20 [&::-webkit-search-cancel-button]:hidden">

    @if ($search)
        <a href="{{ route($route, $params) }}" aria-label="Clear search"
           class="absolute top-1/2 right-2 -translate-y-1/2 rounded p-0.5 text-slate-400 transition hover:text-slate-700 dark:hover:text-slate-200">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
        </a>
    @endif
</form>
