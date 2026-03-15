<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('app:clear-error-pages')->hourly();

Schedule::command('parser:manager')->everyMinute();
Schedule::command('app:counts')->everyMinute();