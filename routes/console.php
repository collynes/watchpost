<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('watchpost:digest --send')->dailyAt(config('watchpost.digest_time'))->timezone(config('watchpost.timezone'))->withoutOverlapping();
