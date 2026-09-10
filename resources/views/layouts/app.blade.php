<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Store Inventory') &middot; Store Inventory</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    @stack('styles')
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased">
    <div class="pointer-events-none fixed inset-0 -z-10 bg-grid-pattern opacity-[0.15]"></div>
    <div class="pointer-events-none fixed inset-x-0 top-0 -z-10 h-96 bg-gradient-to-b from-indigo-100/60 via-transparent to-transparent"></div>

    <div class="flex min-h-screen flex-col">
        <nav class="sticky top-0 z-40 border-b border-white/10 bg-gradient-to-r from-slate-950 via-slate-900 to-indigo-950 shadow-lg shadow-slate-900/10">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6">
                <a href="{{ route('orders.index') }}" class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 text-white shadow-md shadow-indigo-900/40">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                        </svg>
                    </span>
                    <span class="leading-tight">
                        <span class="block text-[15px] font-bold text-white">Store<span class="text-indigo-400">Inventory</span></span>
                        <span class="hidden text-[11px] font-medium tracking-wide text-slate-400 sm:block">Order &amp; billing console</span>
                    </span>
                </a>

                <div class="flex items-center gap-1.5">
                    <a href="{{ route('orders.index') }}"
                       class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-sm font-medium transition-colors {{ request()->routeIs('orders.index') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Orders
                    </a>
                </div>
            </div>
        </nav>

        <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6">
            @if (session('success'))
                <div class="mb-6 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3.5 text-sm text-emerald-800 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mt-0.5 h-5 w-5 shrink-0 text-emerald-500">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="flex-1 font-medium">{{ session('success') }}</span>
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="border-t border-slate-200 bg-white/60 py-5">
            <div class="mx-auto max-w-7xl px-4 text-center text-xs text-slate-400 sm:px-6">
                &copy; {{ date('Y') }} Store Inventory &middot; Crafted for fast, friendly billing
            </div>
        </footer>
    </div>

    <div id="toast-container" class="fixed top-4 right-4 z-[100] flex w-full max-w-xs flex-col gap-2"></div>

    @stack('scripts')
</body>
</html>
