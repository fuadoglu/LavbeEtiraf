<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('lavbe:deliver-notifications')->everyMinute()->withoutOverlapping();
Schedule::command('lavbe:indexnow')->everyMinute()->withoutOverlapping();
Schedule::command('lavbe:prune-expired-cache')->daily()->withoutOverlapping();
