@php
    $grade = $scan->grade_letter;

    $gradeTone = match ($grade) {
        'A', 'B' => 'text-pass',
        'C', 'D' => 'text-warn',
        default => 'text-fail',
    };

    $severityTone = fn (string $s) => match ($s) {
        'critical', 'high' => 'text-fail',
        'medium' => 'text-warn',
        default => 'text-muted',
    };

    $findings = $scan->findings ?? [];
    $passed = $scan->passed ?? [];
    $done = $scan->checks_done ?? 0;
    $total = $scan->checks_total ?? count($checkTitles);
@endphp

<div @if ($scan->isRunning()) wire:poll.750ms @endif class="pt-20 sm:pt-24">
    @if ($scan->isRunning())
        {{-- Live: the checks are assertions, ticking off as each response lands. --}}
        <section aria-labelledby="scanning-heading">
            <h2 id="scanning-heading" class="font-mono text-[11px] uppercase tracking-[0.14em] text-faint">
                @if ($scan->isPending()) Queued @else Scanning @endif
            </h2>

            <h1 class="mt-5 break-all text-4xl font-medium tracking-[-0.03em]">{{ $scan->host }}</h1>

            <p class="mt-3 font-mono text-[13px] text-muted">{{ $done }} / {{ $total }} checks</p>

            <ul class="mt-10 grid gap-3 md:grid-cols-2" role="list" aria-live="polite">
                @foreach ($checkTitles as $i => $title)
                    @php $state = $i < $done ? 'done' : ($i === $done ? 'active' : 'pending'); @endphp

                    <li class="panel flex items-baseline gap-3 p-5 text-[13px] leading-relaxed
                               {{ $state === 'pending' ? 'text-faint' : 'text-ink' }}
                               {{ $state === 'active' ? 'working' : '' }}">
                        <span class="w-3 shrink-0 font-mono text-faint" aria-hidden="true">
                            {{ $state === 'done' ? '✓' : ($state === 'active' ? '›' : '·') }}
                        </span>
                        <span>{{ $title }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @elseif ($scan->isFailed())
        <section class="max-w-xl" aria-labelledby="failed-heading">
            <h2 id="failed-heading" class="font-mono text-[11px] uppercase tracking-[0.14em] text-faint">
                Scan failed
            </h2>

            <h1 class="mt-5 text-3xl font-medium tracking-[-0.03em]">That scan didn’t finish</h1>

            <p class="mt-4 text-[17px] leading-relaxed text-ink">
                {{ $scan->error ?? 'The site didn’t respond. Check the URL and try again.' }}
            </p>

            <a href="{{ route('home') }}" wire:navigate
               class="mt-8 inline-flex h-12 items-center rounded-xl bg-ink px-6 text-sm font-medium text-surface transition hover:bg-ink/85">
                Scan another site
            </a>
        </section>
    @else
        {{-- The verdict. The grade is the one thing on this page allowed to carry colour. --}}
        <header class="rise" aria-labelledby="result-heading">
            <div class="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-2">
                <h2 id="result-heading" class="font-mono text-[11px] uppercase tracking-[0.14em] text-faint">
                    Result
                </h2>
                <p class="font-mono text-[11px] uppercase tracking-[0.14em] text-faint">
                    Scanned {{ $scan->completed_at?->diffForHumans() }}
                </p>
            </div>

            <h1 class="mt-5 break-all text-4xl font-medium tracking-[-0.03em] sm:text-5xl">{{ $scan->host }}</h1>

            <div class="mt-10 flex flex-col gap-8 sm:flex-row sm:items-end sm:gap-12">
                <p class="text-[7rem] font-medium leading-[0.8] tracking-tighter {{ $gradeTone }}">
                    {{ $grade }}
                </p>

                <div class="flex-1">
                    <x-grade-strip :earned="$grade" />

                    <p class="mt-5 max-w-lg text-[17px] leading-relaxed text-ink">
                        @if (count($findings) === 0)
                            Nothing exposed across {{ count($passed) }} checks. Scored
                            {{ $scan->grade_score }} out of 100.
                        @else
                            <span class="font-medium">{{ count($findings) }} issue{{ count($findings) === 1 ? '' : 's' }}</span>
                            found, {{ count($passed) }} check{{ count($passed) === 1 ? '' : 's' }} passed. Scored
                            {{ $scan->grade_score }} out of 100.
                        @endif
                    </p>
                </div>
            </div>
        </header>

        @if (count($findings) > 0)
            <section class="mt-20" aria-labelledby="findings-heading">
                <h2 id="findings-heading" class="font-mono text-[11px] uppercase tracking-[0.14em] text-faint">
                    What we found
                </h2>

                <div class="mt-5 space-y-3">
                    @foreach ($findings as $i => $finding)
                        <article class="rise panel p-6 sm:p-8" style="animation-delay: {{ min($i * 50, 250) }}ms">
                            <p class="font-mono text-[11px] uppercase tracking-[0.14em] {{ $severityTone($finding['severity']) }}">
                                {{ $finding['severity'] }}
                            </p>

                            <h3 class="mt-3 text-xl font-medium leading-snug tracking-[-0.02em]">
                                {{ $finding['title'] }}
                            </h3>

                            <p class="mt-3 max-w-2xl leading-relaxed text-muted">{{ $finding['explanation'] }}</p>

                            @if (! empty($finding['evidence']))
                                <p class="mt-4 overflow-x-auto rounded-xl bg-ground px-4 py-3 font-mono text-[12px] leading-relaxed text-muted">
                                    {{ $finding['evidence'] }}
                                </p>
                            @endif

                            <div class="mt-6 border-t border-rule pt-6">
                                <p class="font-mono text-[11px] uppercase tracking-[0.14em] text-faint">The fix</p>
                                <p class="mt-3 max-w-2xl text-[17px] leading-relaxed text-ink">{{ $finding['fix'] }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        @if (count($passed) > 0)
            <section class="mt-16" aria-labelledby="passed-heading">
                <h2 id="passed-heading" class="font-mono text-[11px] uppercase tracking-[0.14em] text-faint">
                    What held up
                </h2>

                <ul class="mt-5 grid gap-3 md:grid-cols-2" role="list">
                    @foreach ($passed as $title)
                        <li class="panel flex items-baseline gap-3 p-5 text-[13px] leading-relaxed text-muted">
                            <span class="w-3 shrink-0 font-mono text-pass" aria-hidden="true">✓</span>
                            <span>{{ $title }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

    @endif
</div>
