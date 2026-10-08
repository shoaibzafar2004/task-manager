<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? 'Tasks' }} · {{ config('app.name') }}</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
        <header class="sticky top-0 z-30 border-b border-slate-200/70 bg-white/80 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-4xl items-center justify-between px-4">
                <a href="{{ route('tasks.index') }}" class="flex items-center gap-2 font-semibold text-slate-900">
                    <span class="grid size-8 place-items-center rounded-lg bg-indigo-600 text-white shadow-sm shadow-indigo-600/30">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                    </span>
                    {{ config('app.name') }}
                </a>

                <nav class="flex gap-1 rounded-lg bg-slate-100 p-1 text-sm font-medium">
                    @foreach (['tasks.index' => 'Tasks', 'history' => 'History'] as $route => $label)
                        <a href="{{ route($route) }}"
                           @class([
                               'rounded-md px-3 py-1.5 transition',
                               'bg-white text-slate-900 shadow-sm' => request()->routeIs($route),
                               'text-slate-500 hover:text-slate-800' => ! request()->routeIs($route),
                           ])>{{ $label }}</a>
                    @endforeach
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-4xl px-4 py-8">
            {{ $slot }}
        </main>

        {{-- Toasts --}}
        <div x-data="toasts(@js(session('status')))"
             @toast.window="push($event.detail)"
             class="pointer-events-none fixed inset-x-0 bottom-4 z-50 flex flex-col items-center gap-2 px-4">
            <template x-for="toast in items" :key="toast.id">
                <div x-show="toast.visible"
                     x-transition:enter="transition duration-300 ease-out"
                     x-transition:enter-start="translate-y-4 opacity-0"
                     x-transition:leave="transition duration-200 ease-in"
                     x-transition:leave-end="translate-y-4 opacity-0"
                     :class="toast.type === 'error' ? 'bg-rose-600' : 'bg-slate-900'"
                     class="pointer-events-auto rounded-lg px-4 py-2.5 text-sm font-medium text-white shadow-lg"
                     x-text="toast.message"></div>
            </template>
        </div>
    </body>
</html>
