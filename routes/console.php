<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule automated backups
Schedule::command('backup:database --compress')->dailyAt('02:00')->description('Daily database backup');

// Schedule fee reminder notifications
Schedule::command('notifications:send-fee-reminders')->dailyAt('09:00')->description('Send fee reminder notifications');

// Schedule automatic invoice generation (runs at the start of each month)
Schedule::command('invoices:generate-scheduled')->monthlyOn(1, '06:00')->description('Auto-generate invoices for new term');
