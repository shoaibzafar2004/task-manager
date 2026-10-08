@props(['name', 'id' => null, 'value' => null, 'maxlength' => null, 'placeholder' => null])

@php($id ??= $name)
@php($tool = 'grid size-7 place-items-center rounded-md text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100')
@php($tab = 'rounded-md px-2.5 py-1 text-xs font-medium transition')

{{-- Markdown textarea with a formatting toolbar and a server-rendered preview --}}
<div x-data="markdownEditor(@js(route('markdown.preview')))"
     class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm transition focus-within:border-indigo-400 focus-within:ring-2 focus-within:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:focus-within:ring-indigo-500/20">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-2 py-1.5 dark:border-slate-800">
        <div class="flex gap-1" role="tablist">
            <button type="button" role="tab" :aria-selected="tab === 'write'" @click="write()"
                    :class="tab === 'write' ? 'bg-slate-100 text-slate-900 dark:bg-slate-800 dark:text-white' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200'"
                    class="{{ $tab }}">Write</button>
            <button type="button" role="tab" :aria-selected="tab === 'preview'" @click="preview()"
                    :class="tab === 'preview' ? 'bg-slate-100 text-slate-900 dark:bg-slate-800 dark:text-white' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200'"
                    class="{{ $tab }}">Preview</button>
        </div>

        <div x-show="tab === 'write'" class="flex flex-wrap items-center gap-0.5" role="toolbar" aria-label="Formatting">
            <button type="button" @click="wrap('**')" class="{{ $tool }}" title="Bold (Ctrl+B)" aria-label="Bold">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linejoin="round" d="M6.75 3.744h-.753v8.25h7.125a4.125 4.125 0 0 0 0-8.25H6.75Zm0 0v.38m0 16.122h6.747a4.5 4.5 0 0 0 0-9.001h-7.5v9h.753Zm0 0v-.37m0-15.751h6a3.75 3.75 0 1 1 0 7.5h-6m0-7.5v7.5m0 0v8.25m0-8.25h6.375a4.125 4.125 0 0 1 0 8.25H6.75m.747-15.38h4.875a3.375 3.375 0 0 1 0 6.75H7.497v-6.75Zm0 7.5h5.25a3.75 3.75 0 0 1 0 7.5h-5.25v-7.5Z"/></svg>
            </button>
            <button type="button" @click="wrap('_')" class="{{ $tool }}" title="Italic (Ctrl+I)" aria-label="Italic">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5.248 20.246H9.05m0 0h3.696m-3.696 0 5.893-16.502m0 0h-3.697m3.697 0h3.803"/></svg>
            </button>
            <button type="button" @click="prefixLines('## ')" class="{{ $tool }}" title="Heading" aria-label="Heading">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.243 4.493v7.5m0 0v7.502m0-7.501h10.5m0-7.5v7.5m0 0v7.501m4.501-8.627 2.25-1.5v10.126m0 0h-2.25m2.25 0h2.25"/></svg>
            </button>
            <span class="mx-1 h-4 w-px bg-slate-200 dark:bg-slate-700"></span>
            <button type="button" @click="prefixLines('- ')" class="{{ $tool }}" title="Bulleted list" aria-label="Bulleted list">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
            </button>
            <button type="button" @click="prefixLines('1. ')" class="{{ $tool }}" title="Numbered list" aria-label="Numbered list">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.242 5.992h12m-12 6.003H20.24m-12 5.999h12M4.117 7.495v-3.75H2.99m1.125 3.75H2.99m1.125 0H5.24m-1.92 2.577a1.125 1.125 0 1 1 1.591 1.59l-1.83 1.83h2.16M2.99 15.745h1.125a1.125 1.125 0 0 1 0 2.25H3.74m0-.002h.375a1.125 1.125 0 0 1 0 2.25H2.99"/></svg>
            </button>
            <button type="button" @click="prefixLines('- [ ] ')" class="{{ $tool }}" title="Checklist" aria-label="Checklist">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </button>
            <span class="mx-1 h-4 w-px bg-slate-200 dark:bg-slate-700"></span>
            <button type="button" @click="link()" class="{{ $tool }}" title="Link" aria-label="Link">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"/></svg>
            </button>
            <button type="button" @click="code()" class="{{ $tool }}" title="Code" aria-label="Code">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5"/></svg>
            </button>
        </div>
    </div>

    <textarea x-ref="input" x-show="tab === 'write'" id="{{ $id }}" name="{{ $name }}" rows="4"
              @if ($maxlength) maxlength="{{ $maxlength }}" @endif placeholder="{{ $placeholder }}"
              @input="resize(); length = $el.value.length"
              @keydown.ctrl.b.prevent="wrap('**')" @keydown.meta.b.prevent="wrap('**')"
              @keydown.ctrl.i.prevent="wrap('_')" @keydown.meta.i.prevent="wrap('_')"
              class="block max-h-[32rem] min-h-24 w-full resize-none overflow-y-auto border-0 bg-transparent px-3 py-2 font-mono text-sm leading-relaxed text-slate-800 placeholder:font-sans placeholder:text-slate-400 focus:ring-0 focus:outline-none dark:text-slate-100 dark:placeholder:text-slate-500">{{ $value }}</textarea>

    <div x-show="tab === 'preview'" x-cloak class="min-h-24 px-3 py-2">
        <p x-show="loading" class="text-sm text-slate-400">Rendering…</p>
        <div x-show="! loading && html" x-html="html" class="prose-task"></div>
        <p x-show="! loading && ! html" class="text-sm text-slate-400 italic dark:text-slate-500">Nothing to preview.</p>
    </div>

    <div class="flex items-center justify-between border-t border-slate-100 px-3 py-1.5 text-xs text-slate-400 dark:border-slate-800 dark:text-slate-500">
        <span>Markdown supported: **bold**, lists, - [ ] checklists, [links](https://…)</span>
        @if ($maxlength)
            <span x-text="`${length.toLocaleString()} / {{ number_format($maxlength) }}`" class="tabular-nums"></span>
        @endif
    </div>
</div>
