<div class="flex flex-1 flex-col justify-center py-12">
    <div class="mx-auto w-full max-w-xl text-center">
        <h1 class="text-balance text-4xl font-bold leading-[1.1] tracking-tight sm:text-5xl">
            Is your Laravel app
            <span class="text-[#F53003]">leaking secrets?</span>
        </h1>
        <p class="mx-auto mt-5 max-w-md text-pretty text-base leading-relaxed text-zinc-500 dark:text-zinc-400">
            Enter your site’s URL for a passive security checkup. We look for exposed
            <code class="rounded bg-zinc-100 px-1 py-0.5 text-[13px] dark:bg-zinc-900">.env</code> files,
            open Telescope and Horizon dashboards, and missing headers — then grade you.
        </p>

        <form wire:submit="scan" class="mx-auto mt-9 max-w-lg">
            <div class="flex flex-col gap-2.5 sm:flex-row">
                <div class="relative flex-1">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-zinc-400">
                        <svg viewBox="0 0 24 24" fill="none" class="h-4.5 w-4.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>
                        </svg>
                    </span>
                    <input
                        type="text"
                        wire:model="url"
                        placeholder="yourapp.com"
                        autocomplete="off"
                        autofocus
                        class="w-full rounded-xl border border-zinc-200 bg-white py-3 pl-10 pr-4 text-[15px] shadow-sm outline-none transition placeholder:text-zinc-400 focus:border-[#F53003] focus:ring-2 focus:ring-[#F53003]/20 dark:border-zinc-800 dark:bg-zinc-900 dark:placeholder:text-zinc-600"
                    >
                </div>
                <button
                    type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-zinc-900 px-6 py-3 text-[15px] font-semibold text-white shadow-sm transition hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-900/30 disabled:opacity-60 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200"
                    wire:loading.attr="disabled"
                    wire:target="scan"
                >
                    <span wire:loading.remove wire:target="scan">Scan now</span>
                    <span wire:loading.flex wire:target="scan" class="items-center gap-2">
                        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                        </svg>
                        Scanning
                    </span>
                </button>
            </div>
            @error('url')
                <p class="mt-2.5 text-left text-sm text-[#F53003]">{{ $message }}</p>
            @enderror
        </form>

        <div class="mt-14 grid grid-cols-2 gap-3 text-left sm:grid-cols-3">
            @foreach ([
                ['Exposed .env', 'App keys & DB credentials'],
                ['Exposed .git', 'Your full source history'],
                ['composer.lock', 'Exact dependency versions'],
                ['Exposed logs', 'laravel.log stack traces'],
                ['Ignition RCE', 'CVE-2021-3129 surface'],
                ['Telescope', 'Every request & query'],
                ['Horizon', 'Queue control panel'],
                ['Pulse', 'App performance internals'],
                ['Directory listing', 'Browsable file indexes'],
                ['HTTPS enforced', 'Plain HTTP redirects up'],
                ['Cookie flags', 'Secure, HttpOnly, SameSite'],
                ['Version banners', 'Server & X-Powered-By'],
                ['Security headers', 'HSTS, CSP, frame options'],
            ] as [$name, $desc])
                <div class="rounded-xl border border-zinc-100 bg-zinc-50/60 px-4 py-3 dark:border-zinc-900 dark:bg-zinc-900/40">
                    <p class="text-sm font-semibold">{{ $name }}</p>
                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </div>
</div>
