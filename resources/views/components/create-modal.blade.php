@props(['show', 'title', 'action', 'bag', 'button', 'maxlength' => 100, 'defaultColor' => '#6366f1'])

{{-- "Name + colour" modal shared by projects and labels. `show` is the Alpine flag that opens it. --}}
@php($bagErrors = $errors->getBag($bag))

<div x-show="{{ $show }}" x-cloak @keydown.escape.window="{{ $show }} = false"
     class="fixed inset-0 z-40 grid place-items-center bg-slate-900/40 p-4 backdrop-blur-sm dark:bg-black/60"
     x-transition.opacity>
    <form method="POST" action="{{ $action }}" @click.outside="{{ $show }} = false"
          x-data="{ color: @js($bagErrors->any() ? old('color', $defaultColor) : $defaultColor) }"
          x-show="{{ $show }}" x-transition.scale.95
          class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-900 dark:ring-1 dark:ring-slate-800">
        @csrf
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ $title }}</h2>

        <label for="{{ $bag }}-name" class="mt-4 mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Name</label>
        <input id="{{ $bag }}-name" name="name" required maxlength="{{ $maxlength }}" autocomplete="off"
               value="{{ $bagErrors->any() ? old('name') : '' }}" x-effect="{{ $show }} && $nextTick(() => $el.focus())"
               class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 focus:outline-none dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:focus:ring-indigo-500/20">
        @if ($bagErrors->has('name')) <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $bagErrors->first('name') }}</p> @endif

        <span class="mt-4 mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Color</span>
        <input type="hidden" name="color" :value="color">
        <div class="flex flex-wrap gap-2">
            @foreach (['#6366f1', '#0ea5e9', '#10b981', '#84cc16', '#f59e0b', '#f97316', '#ef4444', '#ec4899', '#8b5cf6', '#64748b'] as $swatch)
                <button type="button" @click="color = '{{ $swatch }}'" aria-label="Color {{ $swatch }}"
                        :class="color === '{{ $swatch }}' ? 'ring-2 ring-offset-2 ring-slate-400 scale-110 dark:ring-offset-slate-900' : ''"
                        class="size-7 rounded-full transition" style="background-color: {{ $swatch }}"></button>
            @endforeach
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <button type="button" @click="{{ $show }} = false" class="rounded-lg px-3.5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">Cancel</button>
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500">{{ $button }}</button>
        </div>
    </form>
</div>
