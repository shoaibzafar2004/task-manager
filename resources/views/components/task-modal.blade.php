{{-- Popup that shows a task over the list or History. Filled by the `taskModal` script. --}}
<div x-data="taskModal" x-show="open" x-cloak x-transition:leave="transition duration-200"
     @keydown.escape.window="open && ! $event.defaultPrevented && close()"
     class="fixed inset-0 z-40 overflow-y-auto">
    <div x-show="open" @click="close()"
         x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0"
         x-transition:leave="transition duration-200 ease-in" x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm dark:bg-black/60"></div>

    <div class="pointer-events-none relative flex min-h-full items-start justify-center p-4 sm:items-center sm:p-8">
        <div x-show="open" x-ref="panel" @keydown.tab="trapFocus($event)"
             x-transition:enter="transition duration-300 ease-out"
             x-transition:enter-start="translate-y-6 scale-95 opacity-0"
             x-transition:enter-end="translate-y-0 scale-100 opacity-100"
             x-transition:leave="transition duration-200 ease-in"
             x-transition:leave-start="translate-y-0 scale-100 opacity-100"
             x-transition:leave-end="translate-y-6 scale-95 opacity-0"
             role="dialog" aria-modal="true" aria-labelledby="task-modal-title"
             class="pointer-events-auto relative w-full max-w-2xl rounded-2xl bg-white shadow-2xl dark:bg-slate-900 dark:ring-1 dark:ring-slate-800">
            <div x-show="loading" class="grid h-64 place-items-center text-slate-400">
                <svg class="size-6 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"/></svg>
            </div>
            <div x-ref="content" x-show="! loading"></div>
        </div>
    </div>
</div>
