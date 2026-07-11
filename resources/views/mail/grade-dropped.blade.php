<x-mail::message>
# Your security grade dropped

The security grade for **{{ $host }}** just went from **{{ $previousGrade }}** to **{{ $newGrade }}**.

We re-scanned it as part of your monitoring, and something changed for the worse.

@if (count($findings) > 0)
**What we found**

@foreach ($findings as $finding)
- **[{{ strtoupper($finding['severity']) }}]** {{ $finding['title'] }}
@endforeach
@endif

<x-mail::button :url="$reportUrl">
View the full scorecard
</x-mail::button>

You’re receiving this because you asked us to watch {{ $host }}.
[Manage or unsubscribe]({{ $manageUrl }}).

Thanks,<br>
Laravel Security Scorecard
</x-mail::message>
