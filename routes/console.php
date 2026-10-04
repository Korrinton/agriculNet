<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Primero los datos de AEMET y después las alertas que dependen de ellos
Schedule::command('aemet:sincronizar')
    ->dailyAt('06:30')
    ->timezone('Europe/Madrid')
    ->withoutOverlapping();

Schedule::command('alertas:generar')
    ->dailyAt('07:00')
    ->timezone('Europe/Madrid')
    ->withoutOverlapping();
