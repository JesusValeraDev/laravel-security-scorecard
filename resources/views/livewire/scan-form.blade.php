{{-- $checks is generated from the registered checks (ScanForm::checkCards), never hand-listed. --}}
<div class="sm:pt-24">
    <section class="mx-auto max-w-3xl text-center">
        <h1 class="rise text-balance text-4xl font-medium leading-[1.1] tracking-[-0.03em] sm:text-[3.25rem]">
            See what your app hands to strangers.
        </h1>

        <p class="rise mx-auto mt-6 max-w-lg text-pretty leading-relaxed text-muted" style="animation-delay: 60ms">
            Enter a domain to get a graded security report for your Laravel app, including leaked files,
            exposed dashboards, missing security headers, and step-by-step fixes.
        </p>

        {{-- The page has one job, so it gets one control. Its shape sets the shape of everything below. --}}
        <form
            wire:submit="scan"
            x-data="{ url: @js($url) }"
            class="rise mt-10"
            style="animation-delay: 120ms"
        >
            {{-- The bar lifts the same amount whether you point at it or type in it. --}}
            <div class="panel group flex items-center gap-3 p-2 pl-5 text-left transition hover:shadow-[0_1px_2px_rgb(15_20_23/0.06),0_16px_36px_-18px_rgb(15_20_23/0.4)] focus-within:border-ink/45 focus-within:shadow-[0_1px_2px_rgb(15_20_23/0.06),0_16px_36px_-18px_rgb(15_20_23/0.4)]">
                {{-- The magnifier is the field's label, so clicking it puts the caret in the field.
                     It darkens with the field itself, not on its own hover — it is not a button. --}}
                <label for="scan-url" class="shrink-0 cursor-text text-faint transition group-has-[input:focus]:text-ink">
                    <span class="sr-only">Domain to scan</span>
                    <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"/><path d="M20 20l-4.3-4.3"/>
                    </svg>
                </label>

                <input
                    id="scan-url"
                    type="text"
                    wire:model="url"
                    x-model="url"
                    placeholder="myapp.com"
                    autocomplete="off"
                    autocapitalize="off"
                    spellcheck="false"
                    autofocus
                    class="h-12 w-full min-w-0 bg-transparent text-lg outline-none placeholder:text-faint focus-visible:outline-none"
                >

                <button
                    type="submit"
                    x-bind:disabled="url.trim() === ''"
                    wire:loading.attr="disabled"
                    wire:target="scan"
                    class="h-12 shrink-0 rounded-xl bg-ink px-6 text-sm font-medium text-surface transition hover:bg-ink/85 disabled:cursor-not-allowed disabled:bg-faint"
                >
                    <span wire:loading.remove wire:target="scan">Scan</span>
                    <span wire:loading wire:target="scan">Starting…</span>
                </button>
            </div>

            @error('url')
                <p class="mt-3 text-left text-sm font-medium text-fail">{{ $message }}</p>
            @enderror
        </form>
    </section>

    @php
        // Two truths, two sections: checks that each cost a request of their own, and checks
        // that cost nothing extra because they all read the same homepage response.
        $ownRequest = array_values(array_filter($checks, fn ($check) => ! $check->isWide()));
        $shared = array_values(array_filter($checks, fn ($check) => $check->isWide()));
    @endphp

    <section class="mt-24" aria-labelledby="requests-heading">
        <h2 id="requests-heading" class="font-mono text-[11px] uppercase tracking-[0.14em] text-faint">
            What we request
        </h2>

        <ul class="mt-5 grid gap-3 md:grid-cols-3" role="list">
            @foreach ($ownRequest as $check)
                <li class="panel flex flex-col gap-2 p-5">
                    <p class="flex flex-wrap gap-x-3 gap-y-1 font-mono text-[13px] leading-relaxed text-ink">
                        @foreach ($check->requests as $request)
                            <span>{{ $request }}</span>
                        @endforeach
                    </p>
                    <p class="text-[13px] leading-relaxed text-muted">{{ $check->reveals[0] }}</p>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- These checks cost no extra request: they all read the one homepage response. --}}
    @foreach ($shared as $check)
        <section class="mt-16" aria-labelledby="shared-heading">
            <h2 id="shared-heading" class="font-mono text-[11px] uppercase tracking-[0.14em] text-faint">
                What your homepage answers
            </h2>

            <ul class="mt-5 grid gap-3 sm:grid-cols-2" role="list">
                @foreach ($check->reveals as $reveals)
                    <li class="panel p-5 text-[13px] leading-relaxed text-muted">{{ $reveals }}</li>
                @endforeach
            </ul>
        </section>
    @endforeach

    {{-- The promise closes the page. Same label-above-content rhythm as every other section;
         its weight comes from being set in ink, not from a box or an indent. --}}
    <section class="mt-16 border-t border-rule pt-8" aria-labelledby="promise-heading">
        <h2 id="promise-heading" class="font-mono text-[11px] uppercase tracking-[0.14em] text-faint">
            How we scan
        </h2>

        <p class="mt-5 max-w-2xl text-[17px] leading-relaxed text-ink">
            Security Scorecard only requests pages your server
            <span class="font-medium">already serves to the public</span>.<br>
            It sends no payloads, submits no forms, exploits nothing, and reports a problem only
            when the response body proves it.
        </p>
    </section>
</div>
