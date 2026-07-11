@php
    $grade = $scan->grade_letter ?? '–';
    // Signature element palette: the grade medallion shifts hue with the verdict.
    $gradeStyle = match ($grade) {
        'A', 'B' => ['ring' => 'ring-emerald-500/25', 'text' => 'text-emerald-600 dark:text-emerald-400', 'bg' => 'bg-emerald-50 dark:bg-emerald-950/40'],
        'C' => ['ring' => 'ring-amber-500/25', 'text' => 'text-amber-600 dark:text-amber-400', 'bg' => 'bg-amber-50 dark:bg-amber-950/40'],
        'D' => ['ring' => 'ring-orange-500/25', 'text' => 'text-orange-600 dark:text-orange-400', 'bg' => 'bg-orange-50 dark:bg-orange-950/40'],
        default => ['ring' => 'ring-red-500/25', 'text' => 'text-red-600 dark:text-red-400', 'bg' => 'bg-red-50 dark:bg-red-950/40'],
    };

    $severityStyle = fn (string $s) => match ($s) {
        'critical' => 'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300',
        'high' => 'border-orange-200 bg-orange-50 text-orange-700 dark:border-orange-900 dark:bg-orange-950/40 dark:text-orange-300',
        'medium' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300',
        default => 'border-zinc-200 bg-zinc-50 text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400',
    };

    $findings = $scan->findings ?? [];
    $passed = $scan->passed ?? [];
@endphp

<div @if ($scan->isRunning()) wire:poll.750ms @endif class="py-8">
    @if ($scan->isRunning())
        {{-- Live progress view --}}
        <div class="mx-auto max-w-md py-10 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-zinc-50 ring-1 ring-zinc-100 dark:bg-zinc-900 dark:ring-zinc-800">
                <svg class="h-6 w-6 animate-spin text-[#F53003]" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/>
                </svg>
            </div>

            <h1 class="mt-5 text-xl font-bold tracking-tight">Scanning {{ $scan->host }}</h1>
            <p class="mt-1.5 h-5 text-sm text-zinc-500 dark:text-zinc-400">
                @if ($scan->isPending())
                    Queued — starting in a moment…
                @elseif ($scan->current_check)
                    Checking: {{ $scan->current_check }}
                @else
                    Finishing up…
                @endif
            </p>

            <div class="mt-6">
                <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                    <div class="h-full rounded-full bg-[#F53003] transition-[width] duration-500 ease-out"
                         style="width: {{ max(4, $scan->progressPercent()) }}%"></div>
                </div>
                <p class="mt-2 text-xs font-medium text-zinc-400">
                    {{ $scan->checks_done }} of {{ $scan->checks_total ?? '–' }} checks
                </p>
            </div>
        </div>
    @elseif ($scan->isFailed())
        <div class="rounded-2xl border border-zinc-200 p-10 text-center dark:border-zinc-800">
            <p class="text-lg font-semibold">We couldn’t finish that scan</p>
            <p class="mx-auto mt-2 max-w-sm text-sm text-zinc-500 dark:text-zinc-400">
                {{ $scan->error ?? 'The site may be unreachable. Check the URL and try again.' }}
            </p>
            <a href="{{ route('home') }}" wire:navigate class="mt-6 inline-block rounded-xl bg-zinc-900 px-5 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900">
                Try another site
            </a>
        </div>
    @else
        {{-- Scorecard header: the grade medallion --}}
        <div class="flex flex-col items-center gap-6 rounded-2xl border border-zinc-100 bg-zinc-50/50 p-8 text-center dark:border-zinc-900 dark:bg-zinc-900/30 sm:flex-row sm:text-left">
            <div class="flex h-28 w-28 shrink-0 items-center justify-center rounded-2xl ring-4 {{ $gradeStyle['ring'] }} {{ $gradeStyle['bg'] }}">
                <span class="text-6xl font-extrabold tracking-tighter {{ $gradeStyle['text'] }}">{{ $grade }}</span>
            </div>
            <div class="flex-1">
                <p class="text-xs font-medium uppercase tracking-wide text-zinc-400">Scorecard for</p>
                <h1 class="mt-0.5 break-all text-2xl font-bold tracking-tight">{{ $scan->host }}</h1>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                    @if (count($findings) === 0)
                        No issues found across {{ count($passed) }} checks. Clean bill of health.
                    @else
                        <span class="font-semibold text-zinc-700 dark:text-zinc-200">{{ count($findings) }} issue{{ count($findings) === 1 ? '' : 's' }}</span>
                        found · {{ count($passed) }} check{{ count($passed) === 1 ? '' : 's' }} passed · scored {{ $scan->grade_score }}/100
                    @endif
                </p>
            </div>
        </div>

        {{-- Findings --}}
        @if (count($findings) > 0)
            <div class="mt-8 space-y-4">
                @foreach ($findings as $finding)
                    <article class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-800">
                        <div class="flex items-start justify-between gap-3">
                            <h2 class="text-base font-semibold leading-snug">{{ $finding['title'] }}</h2>
                            <span class="shrink-0 rounded-full border px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide {{ $severityStyle($finding['severity']) }}">
                                {{ $finding['severity'] }}
                            </span>
                        </div>
                        <p class="mt-2.5 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">{{ $finding['explanation'] }}</p>
                        <div class="mt-3.5 rounded-xl bg-zinc-50 p-3.5 dark:bg-zinc-900/60">
                            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-400">How to fix</p>
                            <p class="mt-1 text-sm leading-relaxed text-zinc-700 dark:text-zinc-200">{{ $finding['fix'] }}</p>
                        </div>
                        @if (! empty($finding['evidence']))
                            <p class="mt-2.5 font-mono text-xs text-zinc-400">{{ $finding['evidence'] }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif

        {{-- Passed checks --}}
        @if (count($passed) > 0)
            <div class="mt-8 rounded-2xl border border-zinc-100 p-5 dark:border-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-400">Passed</p>
                <ul class="mt-3 grid gap-2 sm:grid-cols-2">
                    @foreach ($passed as $title)
                        <li class="flex items-start gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                            <svg viewBox="0 0 24 24" fill="none" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 6L9 17l-5-5"/>
                            </svg>
                            {{ $title }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mt-8 flex items-center justify-between border-t border-zinc-100 pt-6 dark:border-zinc-900">
            <p class="text-xs text-zinc-400">Scanned {{ $scan->completed_at?->diffForHumans() }}</p>
            <a href="{{ route('home') }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-xl bg-zinc-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                Scan another site
            </a>
        </div>
    @endif
</div>
