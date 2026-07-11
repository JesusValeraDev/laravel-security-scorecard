<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Grade-drop email alerts
    |--------------------------------------------------------------------------
    |
    | When disabled, monitors are still re-scanned and their grade history is
    | updated, but no email is sent on a grade drop. This keeps the app free to
    | run (no mail provider required). Flip to true once you have configured a
    | free mail transport (e.g. the "log" driver, Resend, Mailgun sandbox).
    |
    */

    'alerts_enabled' => (bool) env('SCORECARD_ALERTS_ENABLED', false),

];
