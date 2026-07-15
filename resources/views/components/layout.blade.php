<!DOCTYPE html>
{{-- Server-render the last resolved theme from a cookie so a wire:navigate morph keeps
     data-theme (no flash of light before JS re-applies). --}}
<html lang="en" class="antialiased" data-theme="{{ request()->cookie('theme_resolved', 'light') }}" style="color-scheme: {{ request()->cookie('theme_resolved', 'light') }}">
@php
    $pageTitle = $title ?? 'Security Scorecard · passive security check for Laravel apps';
    $pageDescription = $description ?? 'Enter a domain to get a graded security report for your Laravel app, including leaked files, exposed dashboards, missing security headers, and step-by-step fixes.';
    // Scan reports are per-scan and may name someone's exposed findings: never index them.
    $noindex = $noindex ?? false;
@endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Resolve the theme before first paint so there is no flash of the wrong mode. Stores the
         user's choice (light/dark/system) but sets a concrete data-theme the CSS can key off. --}}
    <script>
        (() => {
            const mq = window.matchMedia('(prefers-color-scheme: dark)');
            const get = () => localStorage.getItem('theme') || 'system';
            const apply = (mode) => {
                const dark = mode === 'dark' || (mode !== 'light' && mq.matches);
                const resolved = dark ? 'dark' : 'light';
                const root = document.documentElement;
                root.dataset.theme = resolved;
                root.style.colorScheme = resolved;
                // Persist the resolved value so the server can render data-theme on the next
                // navigation and the morph does not flash light before JS runs.
                document.cookie = 'theme_resolved=' + resolved + ';path=/;max-age=31536000;samesite=lax';
            };
            window.__theme = { get, apply, set: (m) => { localStorage.setItem('theme', m); apply(m); } };
            apply(get());
            mq.addEventListener('change', () => { if (get() === 'system') apply('system'); });
            // wire:navigate morphs a fresh server DOM that has no data-theme, so re-apply
            // the stored choice after each SPA navigation to avoid reverting to light.
            document.addEventListener('livewire:navigated', () => apply(get()));
        })();
    </script>

    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    @if ($noindex)
        <meta name="robots" content="noindex, follow">
    @else
        <link rel="canonical" href="{{ url()->current() }}">
    @endif

    {{-- Social share card. The report-card verdict is the whole pitch, so the preview must carry it. --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Security Scorecard">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/og.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Security Scorecard — passive security check for Laravel apps">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ asset('images/og.png') }}">

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

    @vite(['resources/css/app.css'])
    @livewireStyles
</head>
<body class="min-h-screen bg-ground font-sans text-ink">
    <div class="mx-auto flex min-h-screen w-full max-w-245 flex-col px-6">
        <header class="flex items-center justify-between py-7">
            {{-- Brand lockup: shield + check + score bars. The only brand colour on the page.
                 It also doubles as the way home, so no separate nav link is needed. --}}
            <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2" aria-label="Security Scorecard — home">
                <svg viewBox="0 0 28 30" fill="none" class="h-6 w-5.5 shrink-0"
                     stroke="var(--color-brand)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"
                     aria-hidden="true">
                    <path d="M14 3 5 6v8.5C5 21 8.5 25.5 14 27.6 19.5 25.5 23 21 23 14.5V6L14 3Z"/>
                    <path d="m9.7 15 3 3 6-6.5"/>
                </svg>

                <span class="text-[16px] font-semibold tracking-tight">
                    Security<span class="text-brand">Scorecard</span>
                </span>
            </a>

            {{-- Cycles light → dark → system. Icon shows the current choice; system tracks the OS. --}}
            <button type="button"
                    x-data="{
                        mode: window.__theme.get(),
                        get nextLabel() {
                            return this.mode === 'light' ? 'Switch to dark theme'
                                : this.mode === 'dark' ? 'Switch to system mode'
                                : 'Switch to light theme';
                        },
                    }"
                    x-cloak
                    @click="mode = mode === 'light' ? 'dark' : mode === 'dark' ? 'system' : 'light'; window.__theme.set(mode)"
                    class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-lg text-muted transition hover:bg-ink/6 hover:text-ink"
                    :aria-label="nextLabel"
                    :title="nextLabel">
                {{-- light --}}
                <svg x-show="mode === 'light'" viewBox="0 0 24 24" fill="none" class="h-4.5 w-4.5"
                     stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="4"/>
                    <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>
                </svg>
                {{-- dark --}}
                <svg x-show="mode === 'dark'" viewBox="0 0 24 24" fill="none" class="h-4.5 w-4.5"
                     stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/>
                </svg>
                {{-- system --}}
                <svg x-show="mode === 'system'" viewBox="0 0 24 24" fill="none" class="h-4.5 w-4.5"
                     stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="12" rx="2"/>
                    <path d="M8 20h8M12 16v4"/>
                </svg>
            </button>
        </header>

        <main class="flex flex-1 flex-col">
            {{ $slot }}
        </main>

        <footer class="mt-24 flex flex-col gap-3 border-t border-rule py-7 sm:flex-row sm:items-center sm:justify-between sm:gap-8">
            <p class="text-[13px] leading-relaxed text-muted">
                Scan applications you own or are authorized to test.
            </p>

            <div class="flex shrink-0 items-center gap-4 font-mono text-[11px] uppercase tracking-[0.14em] text-faint">
                <a href="https://github.com/JesusValeraDev/laravel-security-scorecard"
                   target="_blank" rel="noopener noreferrer" class="transition hover:text-ink" aria-label="Source on GitHub">
                    <svg viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4" aria-hidden="true">
                        <path d="M12 .5C5.37.5 0 5.87 0 12.5c0 5.3 3.44 9.8 8.21 11.39.6.11.82-.26.82-.58 0-.29-.01-1.04-.02-2.05-3.34.73-4.04-1.61-4.04-1.61-.55-1.39-1.34-1.76-1.34-1.76-1.09-.75.08-.73.08-.73 1.2.09 1.84 1.24 1.84 1.24 1.07 1.83 2.81 1.3 3.5.99.11-.78.42-1.3.76-1.6-2.67-.3-5.47-1.33-5.47-5.93 0-1.31.47-2.38 1.24-3.22-.12-.3-.54-1.52.12-3.17 0 0 1.01-.32 3.3 1.23a11.5 11.5 0 0 1 6 0c2.29-1.55 3.3-1.23 3.3-1.23.66 1.65.24 2.87.12 3.17.77.84 1.24 1.91 1.24 3.22 0 4.61-2.81 5.63-5.49 5.92.43.37.81 1.1.81 2.22 0 1.6-.01 2.9-.01 3.29 0 .32.22.7.83.58A12.01 12.01 0 0 0 24 12.5C24 5.87 18.63.5 12 .5Z"/>
                    </svg>
                </a>
                <a href="https://jesusvalera.dev/" target="_blank" rel="me" class="transition hover:text-ink" aria-label="jesusvalera.dev">
                    <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4" stroke="currentColor"
                         stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M3 12h18"/>
                        <path d="M12 3a13.5 13.5 0 0 1 0 18 13.5 13.5 0 0 1 0-18Z"/>
                    </svg>
                </a>
            </div>
        </footer>
    </div>

    @livewireScripts
</body>
</html>
