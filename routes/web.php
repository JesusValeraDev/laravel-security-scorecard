<?php

use Illuminate\Support\Facades\Route;
use Modules\Scorecard\Infrastructure\Http\Livewire\ScanForm;
use Modules\Scorecard\Infrastructure\Http\Livewire\ScanReport;

Route::get('/', ScanForm::class)->name('home');

Route::get('/r/{scan}', ScanReport::class)->name('report');
