<?php
use Illuminate\Support\Facades\Schedule;

Schedule::command('files:delete-expired')
    ->everyMinute()
    ->withoutOverlapping();
