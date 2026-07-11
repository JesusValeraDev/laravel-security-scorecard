<!DOCTYPE html>
<html lang="en" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Laravel Security Scorecard' }}</title>
    <meta name="description" content="A passive security checkup for your Laravel app. Get a graded, shareable report card in seconds.">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">

    @vite(['resources/css/app.css'])
    @livewireStyles
</head>
<body class="min-h-screen bg-white text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100 [font-family:'Instrument_Sans',ui-sans-serif,system-ui,sans-serif]">
    <div class="mx-auto flex min-h-screen max-w-3xl flex-col px-6">
        <header class="flex items-center justify-between py-6">
            <a href="{{ route('home') }}" class="group flex items-center gap-2.5">
                <span class="flex h-7 w-7 items-center justify-center rounded-md bg-[#F53003] text-white shadow-sm">
                    <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3l7 3v5c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3z"/>
                    </svg>
                </span>
                <span class="text-sm font-semibold tracking-tight">Security Scorecard</span>
            </a>
            <span class="rounded-full border border-zinc-200 px-2.5 py-1 text-[11px] font-medium text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                for Laravel
            </span>
        </header>

        <main class="flex flex-1 flex-col">
            {{ $slot }}
        </main>

        <footer class="py-8 text-center text-xs text-zinc-400 dark:text-zinc-600">
            Passive checks only — we never attempt exploitation. Scan sites you own.
        </footer>
    </div>

    @livewireScripts
</body>
</html>
