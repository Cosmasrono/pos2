<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run every night at 01:00 AM: generate fresh predictions and reconcile past ones
Schedule::command('predictions:generate')->dailyAt('01:00')->withoutOverlapping();
