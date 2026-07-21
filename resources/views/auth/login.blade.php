<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Login - {{ config('app.name', 'School ERP') }}</title>
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
            <script>
                tailwind.config = {
                    darkMode: 'class',
                    theme: {
                        extend: {
                            fontFamily: {
                                sans: ['system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'sans-serif'],
                            },
                        },
                    },
                };
            </script>
        @endif
    </head>
    <body class="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center">
        <div class="absolute inset-0 bg-gradient-to-br from-indigo-600/30 via-slate-900 to-emerald-500/20 blur-3xl opacity-60"></div>
        <div class="relative w-full max-w-md mx-4">
            <div class="mb-6 text-center">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-slate-700/60 bg-slate-900/80 text-[11px] text-slate-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Integrated School ERP</span>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 shadow-xl shadow-slate-900/60 px-7 py-8 backdrop-blur-xl">
                <div class="flex flex-col items-center mb-6">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-indigo-500 text-sm font-bold tracking-tight">SE</span>
                    <h1 class="mt-3 text-lg font-semibold tracking-tight">Sign in to School ERP</h1>
                    <p class="mt-1 text-xs text-slate-400">Centralized academics, finance, and administration.</p>
                </div>

                @if ($errors->any())
                    <div class="mb-4 rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-xs text-rose-100">
                        <ul class="list-disc ml-4 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ url('/login') }}" class="space-y-4">
                    @csrf
                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-slate-200" for="email">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="w-full rounded-lg border border-slate-700 bg-slate-900/80 px-3 py-2 text-sm text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-slate-200" for="password">Password</label>
                        <input id="password" type="password" name="password" required class="w-full rounded-lg border border-slate-700 bg-slate-900/80 px-3 py-2 text-sm text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>

                    <div class="flex items-center justify-between text-xs text-slate-400">
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="remember" class="h-3 w-3 rounded border-slate-600 bg-slate-900 text-indigo-500 focus:ring-indigo-500">
                            <span>Remember me</span>
                        </label>
                    </div>

                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg bg-indigo-500 px-4 py-2 text-sm font-medium text-white shadow-sm shadow-indigo-500/40 hover:bg-indigo-600 transition">
                        Log in
                    </button>

                    <p class="mt-3 text-[11px] text-slate-400 text-center">
                        Demo admin: <span class="font-mono">admin@mainschool.test</span> / <span class="font-mono">password</span>
                    </p>
                </form>
            </div>
        </div>
    </body>
</html>
