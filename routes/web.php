<?php

use Illuminate\Support\Facades\Route;
use Modules\Scorecard\Infrastructure\Http\Livewire\ScanForm;

Route::get('/', ScanForm::class)->name('home');
