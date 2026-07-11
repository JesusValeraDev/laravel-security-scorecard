<?php

use Modules\Scorecard\Infrastructure\Provider\ScorecardServiceProvider;
use Modules\Shared\Infrastructure\Provider\SharedServiceProvider;

return [
    SharedServiceProvider::class,
    ScorecardServiceProvider::class,
];
