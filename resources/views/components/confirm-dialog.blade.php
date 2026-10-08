{{-- Themed replacement for window.confirm(): any <form data-confirm="…"> asks here before submitting --}}
<div x-data="confirmDialog" x-show="open" x-cloak x-transition:leave="transition duration-150"
     @keydown.escape.window="if (open) { $event.preventDefault(); cancel(); }"
     class="fixed inset-0 z-50 grid place-items-center p-4">
    <div x-show="open" x-transition.opacity.duration.200ms @click="cancel()"
         class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm dark:bg-black/60"></div>

    <div x-show="open"
         x-transition:enter="transition duration-200 ease-out"
         x-transition:enter-start="translate-y-2 scale-95 opacity-0"
         x-transition:leave="transition duration-150 ease-in"
         x-transition:leave-end="translate-y-2 scale-95 opacity-0"
         @keydown.tab.prevent="$event.shiftKey || document.activeElement === $refs.confirm ? $refs.cancel.focus() : $refs.confirm.focus()"
         role="alertdialog" aria-modal="true" aria-labelledby="confirm-title" aria-describedby="confirm-message"
         class="relative w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-900 dark:ring-1 dark:ring-slate-800">
        <div class="flex gap-4">
            <div class="grid size-10 shrink-0 place-items-center rounded-full bg-rose-100 text-rose-600 dark:bg-rose-500/15 dark:text-rose-400">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
            </div>
            <div class="min-w-0">
                <h2 id="confirm-title" class="text-lg font-semibold text-slate-900 dark:text-white" x-text="title"></h2>
                <p id="confirm-message" class="mt-1 text-sm wrap-break-word text-slate-500 dark:text-slate-400" x-text="message"></p>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <button type="button" x-ref="cancel" @click="cancel()"
                    class="rounded-lg px-3.5 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 focus-visible:ring-2 focus-visible:ring-slate-300 focus-visible:outline-none dark:text-slate-300 dark:hover:bg-slate-800 dark:focus-visible:ring-slate-600">Cancel</button>
            <button type="button" x-ref="confirm" @click="accept()" :disabled="busy"
                    class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white shadow-sm shadow-rose-600/30 transition hover:bg-rose-500 focus-visible:ring-2 focus-visible:ring-rose-300 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-wait disabled:opacity-70 dark:focus-visible:ring-offset-slate-900">
                <svg x-show="busy" class="size-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"/></svg>
                <span x-text="button"></span>
            </button>
        </div>
    </div>
</div>
