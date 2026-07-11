<?php

use Illuminate\Support\Facades\Schedule;

// Re-scan due monitors hourly; the command honours each monitor's interval.
Schedule::command('monitors:rescan')->hourly()->withoutOverlapping();
