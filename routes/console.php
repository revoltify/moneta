<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('moneta:process-recurring')
    ->daily();
