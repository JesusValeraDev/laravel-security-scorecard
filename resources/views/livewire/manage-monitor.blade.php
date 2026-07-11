<div class="py-8">
    <div class="mx-auto max-w-lg">
        <p class="text-xs font-medium uppercase tracking-wide text-zinc-400">Monitoring</p>
        <h1 class="mt-0.5 break-all text-2xl font-bold tracking-tight">{{ $monitor->host }}</h1>

        <div class="mt-6 space-y-3 rounded-2xl border border-zinc-200 p-5 text-sm dark:border-zinc-800">
            <div class="flex items-center justify-between">
                <span class="text-zinc-500 dark:text-zinc-400">Alert email</span>
                <span class="font-medium">{{ $monitor->email }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-zinc-500 dark:text-zinc-400">Status</span>
                @if (! $monitor->isVerified())
                    <span class="rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">Awaiting verification</span>
                @elseif ($monitor->active)
                    <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">Active</span>
                @else
                    <span class="rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-semibold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">Paused</span>
                @endif
            </div>
            <div class="flex items-center justify-between">
                <span class="text-zinc-500 dark:text-zinc-400">Last grade</span>
                <span class="font-medium">{{ $monitor->last_grade_letter ?? '—' }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-zinc-500 dark:text-zinc-400">Re-scan frequency</span>
                <span class="font-medium">Every {{ $monitor->frequency_hours }}h</span>
            </div>
        </div>

        <div class="mt-6">
            @if ($monitor->active)
                <button
                    type="button"
                    wire:click="unsubscribe"
                    class="inline-flex items-center justify-center rounded-xl border border-zinc-300 px-5 py-2.5 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-900"
                >
                    Stop watching this site
                </button>
                <p class="mt-2 text-xs text-zinc-400">This pauses re-scans and alerts. You can resume any time from this page.</p>
            @else
                <div class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-800 dark:bg-zinc-900/30">
                    <p class="text-sm text-zinc-600 dark:text-zinc-300">You’ve stopped watching {{ $monitor->host }}. No more re-scans or alerts.</p>
                    <button
                        type="button"
                        wire:click="resubscribe"
                        class="mt-3 inline-flex items-center justify-center rounded-xl bg-zinc-900 px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900"
                    >
                        Resume monitoring
                    </button>
                </div>
            @endif
        </div>

        <a href="{{ route('home') }}" wire:navigate class="mt-8 inline-block text-sm text-zinc-400 underline underline-offset-2 hover:text-zinc-600 dark:hover:text-zinc-200">
            ← Back to scanner
        </a>
    </div>
</div>
