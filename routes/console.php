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

// Grados-día de las viñas con los datos recién descargados
Schedule::command('grados-dia:recalcular')
    ->dailyAt('06:50')
    ->timezone('Europe/Madrid')
    ->withoutOverlapping();

Schedule::command('alertas:generar')
    ->dailyAt('07:00')
    ->timezone('Europe/Madrid')
    ->withoutOverlapping();

// El resumen diario por correo, con las alertas recién generadas
Schedule::command('alertas:enviar')
    ->dailyAt('07:10')
    ->timezone('Europe/Madrid')
    ->withoutOverlapping();

// El registro de fitosanitarios cambia poco: altas, renovaciones y cancelaciones
Schedule::command('fitosanitarios:importar')
    ->weeklyOn(1, '05:30')
    ->timezone('Europe/Madrid')
    ->withoutOverlapping();

// Plazos de seguridad oficiales: unas cuantas fichas PDF cada día para no cargar el servidor del MAPA
Schedule::command('fitosanitarios:fichas --limite=150')
    ->dailyAt('05:45')
    ->timezone('Europe/Madrid')
    ->withoutOverlapping();
