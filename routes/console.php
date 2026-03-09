<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

if ((bool) env('PARTNER_STOCK_SCHEDULE_ENABLED', false)) {
    Schedule::command('woocommerce:sync-partner-stocks')
        ->cron((string) env('PARTNER_STOCK_SCHEDULE_CRON', '*/30 * * * *'))
        ->withoutOverlapping()
        ->description('Sync partner stock into WooCommerce');
}
