@props(['list', 'nextUrl' => null])

{{-- Loads the next batch into #{{ $list }} when scrolled into view; the button is the fallback --}}
<div data-load-more data-list="{{ $list }}" data-next-url="{{ $nextUrl }}" @class(['mt-4 flex justify-center', 'hidden' => ! $nextUrl])>
    <button type="button"
            class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 disabled:cursor-wait dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200">
        <svg class="load-more-spinner hidden size-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"/></svg>
        <span>Load more</span>
    </button>
</div>
