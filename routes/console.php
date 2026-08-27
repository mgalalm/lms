<?php

use App\Console\Commands\GenerateSessionPrepTasksCommand;
use App\Console\Commands\SyncNotionTutorGuidesCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(SyncNotionTutorGuidesCommand::class)->daily();
Schedule::command(GenerateSessionPrepTasksCommand::class)->hourly();
