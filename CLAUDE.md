# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

**agriculNet** ("Programa Agrario") — a Laravel web app for Spanish viticulture management. Farmers register their *fincas* (estates) and *parcelas* (plots), log phenological observations, phytosanitary treatments, meteorological data, and generate the mandatory CUE (*Cuaderno de Explotación*) report required by Spanish agricultural regulations.

## Commands

```bash
# First-time setup (installs deps, generates key, migrates, builds assets)
composer run setup

# Start all dev processes concurrently (server + queue + pail + vite)
composer run dev

# Run all tests
composer run test

# Run a single test class or method
php artisan test --filter=FincaTest
php artisan test --filter=test_usuario_puede_crear_finca_con_parcelas

# Code style (Laravel Pint)
./vendor/bin/pint

# Asset build
npm run build
```

Tests require a running PostgreSQL container (via Docker). The test database is `agrario_test` — the full Docker stack must be up for tests to pass.

## Stack

- **Laravel 13.8** / PHP 8.3 — using the new `Application::configure()` bootstrap style
- **Livewire 3 + Volt** — for reactive components; auth pages are Volt components under `resources/views/livewire/`
- **Tailwind CSS 3** — built via Vite with `@tailwindcss/forms`
- **PostgreSQL 16 + Redis 7** — via Docker Compose; Redis handles cache, queues, and sessions
- **Docker**: nginx → php-fpm → postgres/redis; separate containers for queue worker and scheduler

## Modular Architecture

All domain code lives under `app/Modules/<ModuleName>/` with a consistent internal structure:

```
app/Modules/<Module>/
├── Http/Controllers/     # Web/ and Api/ sub-dirs where needed
├── Models/
├── Routes/
│   ├── api.php           # prefixed /api, middleware api
│   └── web.php           # middleware web
├── Services/
├── Jobs/                 # (where applicable)
└── Events/               # (where applicable)
```

**Route loading**: `ModulesServiceProvider` (`app/Providers/ModulesServiceProvider.php`) auto-loads `api.php` and `web.php` from every module listed in its `$modules` array. To add a new module, add its name to that array.

**Current modules**: `Vinedo`, `CalendarioFenologico`, `Meteorologia`, `Tratamientos`, `Costes`, `CuadernoDigital`, `Riegos`, `Alertas`, `Usuarios`.

## Domain Model

`Parcela` is the central entity — most domain activity is scoped to a plot:

```
User
 └── Finca (estate — provincia/municipio + SIGPAC reference)
      └── Parcela (plot — uses SoftDeletes)
           ├── RegistroFenologico  (phenological observations)
           ├── Tratamiento         (phytosanitary treatments)
           ├── Coste               (cost entries)
           └── Alerta              (alerts)
```

Parcelas are not only vineyards: `Parcela::CULTIVO_POR_USO` maps each `uso` (Secano, Viña en espaldera, Viña en vaso, Olivar, Pistachos) to the `cultivo` of the `Variedad` it accepts (`herbaceo`, `vid`, `olivo`, `pistacho`), enforced by `VariedadDelCultivo` and filtered client-side by `vinedo/parcelas/_variedades_por_uso`. Vine-specific features (BBCH phenology calendar, frost and downy mildew alerts) apply only to `Parcela::vina()`.

`GradosDia` (degree days) are calculated with a default base temperature of 10 °C by `GradosDiaCalculator`, crossing `DatoMeteorologico` data from the nearest `EstacionMeteorologica` linked to the `Finca`.

## Authorization

Policies are registered in `AppServiceProvider`. Current pattern: `user_id` on `Finca` — all policy methods check `$user->id === $finca->user_id`. Controllers call `$this->authorize('view|update|delete', $finca)`.

## External Integrations

- **SIGPAC** (Spanish land registry): `SigpacService` builds visor URLs and fetches GeoJSON via `sigpac-hubcloud.es`. Requires `provincia_cod`, `municipio_cod`, `poligono`, and `parcela_sigpac` on the parcela.
- **AEMET** (meteorological agency): `AemetClient` talks to `opendata.aemet.es` (two-step API, ISO-8859-1 payloads; needs `AEMET_API_KEY`). `ImportadorMeteorologico` imports observed daily data per station (`DatoMeteorologico`, published with ~4 days of lag) and the 7-day municipal forecast (`PrediccionMeteorologica`, keyed by INE code `PPMMM` = `Finca::codigo_ine`). AEMET stations are shared between fincas: users cannot write or delete their data, only data of their own manual stations.

## Scheduled tasks

Defined in `routes/console.php`, run by the `scheduler` container (Europe/Madrid times): `aemet:sincronizar` at 06:30, then `alertas:generar` at 07:00 (end of safety periods, observed and forecast frosts, downy mildew "3-10" rule). Alert generators are idempotent through the unique `alertas.clave` column. A separate `db-backup` container dumps the database to `backups/` daily.
- **Cuaderno de explotación (CUE)**: `CuadernoCampana` assembles a finca's campaign (Orden APA/204/2023 sections: general data and plots, phytosanitary treatments, fertilisation, harvest, irrigation) plus compliance warnings; `ExportadorCUE` writes it to .xlsx (PhpSpreadsheet) and `cuaderno/imprimir` is the printable/PDF version. Electronic submission to SIEX is only possible for MAPA-authorised entities, so there is no XML export. Irrigation records (`Riego`, module Riegos) are only allowed on non-`Secano` parcels (`Parcela::regable()`). The old `cuaderno_entradas` table/`CuadernoEntrada` model are unused.
