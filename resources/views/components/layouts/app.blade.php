<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? 'Tasks' }} · {{ config('app.name') }}</title>

        {{-- Apply the saved (or system) theme before first paint to avoid a flash --}}
        <script>
            try {
                const theme = localStorage.getItem('theme');
                if (theme === 'dark' || (! theme && matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            } catch {}
        </script>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased dark:bg-slate-950 dark:text-slate-200">
        <header class="sticky top-0 z-30 border-b border-slate-200/70 bg-white/80 backdrop-blur dark:border-slate-800 dark:bg-slate-900/80">
            <div class="mx-auto flex h-16 max-w-4xl items-center justify-between gap-3 px-4">
                <a href="{{ route('tasks.index') }}" class="flex items-center gap-2 font-semibold text-slate-900 dark:text-white">
                    <span class="grid size-8 place-items-center rounded-lg bg-indigo-600 text-white shadow-sm shadow-indigo-600/30">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                    </span>
                    <span class="hidden sm:inline">{{ config('app.name') }}</span>
                </a>

                <div class="flex items-center gap-2">
                    <nav class="flex gap-1 rounded-lg bg-slate-100 p-1 text-sm font-medium dark:bg-slate-800">
                        @foreach (['tasks.index' => 'Tasks', 'history' => 'History'] as $route => $label)
                            <a href="{{ route($route) }}"
                               @class([
                                   'rounded-md px-3 py-1.5 transition',
                                   'bg-white text-slate-900 shadow-sm dark:bg-slate-700 dark:text-white' => request()->routeIs($route),
                                   'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200' => ! request()->routeIs($route),
                               ])>{{ $label }}</a>
                        @endforeach
                    </nav>

                    <button type="button" x-data="themeToggle" @click="toggle" :aria-label="dark ? 'Switch to light mode' : 'Switch to dark mode'"
                            class="grid size-9 place-items-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100">
                        {{-- Moon (shown in light mode) --}}
                        <svg x-show="! dark" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z"/></svg>
                        {{-- Sun (shown in dark mode) --}}
                        <svg x-show="dark" x-cloak class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/></svg>
                    </button>
                </div>
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
                     :class="toast.type === 'error' ? 'bg-rose-600' : 'bg-slate-900 dark:bg-slate-700'"
                     class="pointer-events-auto rounded-lg px-4 py-2.5 text-sm font-medium text-white shadow-lg"
                     x-text="toast.message"></div>
            </template>
        </div>
    </body>
</html>
