<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Polymarket trading bot: scan for edges, alert and (optionally) trade.
// Runs every five minutes; overlapping runs are prevented so a slow scan
// (e.g. waiting on the LLM) never stacks.
Schedule::command('polymarket:scan')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();
