@props(['options', 'selected' => null, 'param', 'allText', 'route', 'params' => []])

<div class="relative">
    <select aria-label="{{ $allText }}"
            onchange="window.location = this.value"
            class="w-full appearance-none rounded-lg border border-slate-200 bg-white py-2 pr-9 pl-3 text-sm font-medium text-slate-700 shadow-sm transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:focus:ring-indigo-500/20">
        <option value="{{ route($route, [...$params, $param => null]) }}">{{ $allText }}</option>
        @foreach ($options as $option)
            <option value="{{ route($route, [...$params, $param => $option->id]) }}" @selected($selected === $option->id)>
                {{ $option->name }}@isset($option->tasks_count) ({{ $option->tasks_count }})@endisset
            </option>
        @endforeach
    </select>
    <svg class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
</div>
