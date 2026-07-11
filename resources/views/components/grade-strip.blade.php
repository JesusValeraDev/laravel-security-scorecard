@props(['earned' => null])

@php
    // A critical finding forces F, so there is no E on this scale.
    $letters = ['A', 'B', 'C', 'D', 'F'];

    $toneFor = fn (string $letter) => match ($letter) {
        'A', 'B' => 'text-pass',
        'C', 'D' => 'text-warn',
        default => 'text-fail',
    };
@endphp

<ul {{ $attributes->merge(['class' => 'flex items-stretch']) }} role="list">
    @foreach ($letters as $letter)
        @php $isEarned = $earned === $letter; @endphp

        <li class="flex-1 border-t-2 pt-2 {{ $isEarned ? 'border-current '.$toneFor($letter) : 'border-rule text-faint' }}">
            <span class="block font-mono text-sm {{ $isEarned ? 'font-medium' : '' }}"
                  @if ($isEarned) aria-current="true" @endif>
                {{ $letter }}@if ($isEarned)<span class="sr-only"> — your grade</span>@endif
            </span>
        </li>
    @endforeach
</ul>
