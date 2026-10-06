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

`docker/php/entrypoint.sh` is copied into the PHP image: after changing it, rebuild (`docker compose build php-fpm queue-worker scheduler`), or the containers keep running the old one. In production (`docker-compose.prod.yml`, see README «Producción») it runs `php artisan seguridad:comprobar` first and refuses to start on an insecure configuration (debug, http, insecure session cookie, example DB password, unencrypted backups, the `test@example.com` user).

Tests require a running PostgreSQL container (via Docker). The test database is `agrario_test` — the full Docker stack must be up for tests to pass. Config must not be cached in development (`docker/php/entrypoint.sh` only caches when `APP_ENV=production`): with a cached config the tests would read the development database, and `tests/TestCase.php` aborts them.

## Stack

- **Laravel 13.8** / PHP 8.3 — using the new `Application::configure()` bootstrap style
- **Livewire 3 + Volt** — for reactive components; auth pages are Volt components under `resources/views/livewire/`
- **Tailwind CSS 3** — built via Vite with `@tailwindcss/forms`
- **PostgreSQL 16 + Redis 7** — via Docker Compose; Redis handles cache, queues, and sessions
- **Mail** — in development the `mailpit` container catches every email (UI at http://localhost:8025; not started in production). Emails: the password reset (translated in `lang/es_ES.json`, Laravel's notification strings use JSON keys) and the daily alerts digest (`alertas:enviar` at 07:10 → `ResumenAlertasCorreo` queues `ResumenAlertas`: unread alerts not yet sent (`alertas.notificada_at`) from the last 3 days, filtered by `users.alertas_por_correo` = todas|avisos|criticas|ninguna, set in the profile). Unsubscribe link is a signed route without login (`alertas.correo.baja`: GET only shows a button because mail scanners open links; POST unsubscribes and is CSRF-exempt for RFC 8058 one-click). Mail theme: `resources/views/vendor/mail/html/themes/agriculnet.css`.
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

**Current modules**: `Vinedo`, `CalendarioFenologico`, `Meteorologia`, `Tratamientos`, `Costes`, `CuadernoDigital`, `Riegos`, `Alertas`, `Usuarios`, `Admin`.

## Domain Model

`Parcela` is the central entity — most domain activity is scoped to a plot:

```
User
 └── Finca (estate — provincia/municipio + SIGPAC reference)
      ├── Coste                    (finca-wide expense when parcela_id is null)
      └── Parcela (plot — uses SoftDeletes)
           ├── RegistroFenologico  (phenological observations)
           ├── Tratamiento         (phytosanitary treatments)
           ├── Coste               (cost entries)
           └── Alerta              (alerts)
```

Every `Coste` has a `finca_id` (filled from the parcela on save); `parcela_id` is optional. A cost without parcela is a general expense of the finca (insurance, agency fees…) recorded once and **not** split among the parcelas, so per-parcela figures (`CosteService`) only count the parcela's own costs.

Parcelas are not only vineyards: `Parcela::CULTIVO_POR_USO` maps each `uso` (Secano, Viña en espaldera, Viña en vaso, Olivar, Pistachos) to the `cultivo` of the `Variedad` it accepts (`herbaceo`, `vid`, `olivo`, `pistacho`), enforced by `VariedadDelCultivo` and filtered client-side by `vinedo/parcelas/_variedades_por_uso`. Vine-specific features (BBCH phenology calendar, frost and downy mildew alerts) apply only to `Parcela::vina()`.

`GradosDia` (degree days) are calculated with a default base temperature of 10 °C by `GradosDiaCalculator`, crossing `DatoMeteorologico` data from the `EstacionMeteorologica` linked to the `Finca`, over the vine cycle (1 Apr – 31 Oct, Winkler). The daily cumulative value of each vine parcela is stored in `grados_dia` by `guardarCampana` (delete + insert of the campaign): the `RecalcularGradosDia` job is dispatched when a finca's station or its manual data change and after a manual AEMET import, and `grados-dia:recalcular` runs daily. The panel reads the stored value (computed on the fly if missing).

## Frontend

- **Panel de inicio** (`PanelController` + `App\Services\PanelInicio`): AEMET 7-day forecast of the selected finca (frost days flagged with `GeneradorAlertas::PREVISION_HELADA_TEMP_MIN`), unread alerts, safety periods in progress, the vine's phenological phase, yearly figures and recent activity. `?finca=` switches finca (only the user's own).
- **Flash messages**: controllers keep using `->with('success'|'error', …)`; `components/avisos` (included in `layouts/app`) shows them as toasts (success closes itself after 6 s, paused on hover; errors stay). Views must not render their own flash blocks.
- **No inline JavaScript** (`ContentSecurityPolicy` middleware: `script-src 'self' 'unsafe-eval'`, no `'unsafe-inline'`; `ContentSecurityPolicyTest` scans the views). Views never contain `<script>` or `on*=` handlers: generic behaviours are `data-*` attributes handled in `resources/js/comportamientos.js` (`data-confirmar`, `data-autoenviar`, `data-ir-a-valor`, `data-imprimir`, `data-alternar`, `data-ocultar`); page code lives in `resources/js/paginas/*.js`, registered with `enPagina('[data-pagina="…"]', fn)` (runs on load and after each `wire:navigate`), reading server data from a `<script type="application/json" data-datos>` inside the root. Blade's `@json()` splits its arguments on commas: build multi-key arrays in `@php` first. Leaflet comes from npm (dynamic import, only on map pages). New external hosts (images, scripts, fonts) must be added to the policy.
- `resources/js/app.js` marks the submit button of any non-GET form as busy (`data-enviando`, spinner, no double submit); opt out with `data-sin-espera`. `.pulsable` gives press feedback. Motion lives in `resources/css/app.css` and every animation has a `prefers-reduced-motion` path.
- The compiled CSS/JS in `public/build` is committed: after changing Blade classes or `resources/css|js`, run `npm run build` and commit the result.

## Authorization

Policies are registered in `AppServiceProvider`. Current pattern: `user_id` on `Finca` — all policy methods check `$user->id === $finca->user_id`. Controllers call `$this->authorize('view|update|delete', $finca)`.

**Personal data (GDPR)**: `BorradoCuenta` deletes an account with all its data (fincas first, which cascade to everything else) and `ExportadorDatosUsuario` (`profile.datos`) exports it all as a .zip (JSON + one CSV per table). A new table holding user, finca or parcela data must be added to both; `DescargaDatosTest` fails if a table with `user_id`/`finca_id`/`parcela_id` is missing from the export, and `Tests\Feature\Concerns\CreaUsuarioConDatos` must create a row in it.

**Backoffice** (module `Admin`, `/admin`, middleware `admin` = `users.is_admin`, granted with `php artisan usuarios:admin email [--quitar]`): aggregated usage metrics (`MetricasUso`), user accounts (block via `users.bloqueado_at`, delete via `BorradoCuenta`, never on oneself or another admin; actions logged), scheduled tasks and catalogues (variedades and categorías de coste editable; AEMET stations and fitosanitarios read-only). It shows account data and aggregates, not the content of a user's explotación. `ComprobarCuentaActiva` (web and api groups) logs out blocked users and stores `ultimo_acceso_at` (at most every 15 min). Task history: `Tareas::escucharEjecuciones()` records every run of a command in `Tareas::CATALOGO` in `ejecuciones_tareas` through Laravel's console events (scheduler, console and queue worker; Laravel disables them in unit tests); "Ejecutar ahora" queues `LanzarTarea`, which stores the output and who launched it (`REDIS_QUEUE_RETRY_AFTER` must exceed its 900 s timeout). A new scheduled command must also be added to `Tareas::CATALOGO`.

## External Integrations

- **SIGPAC** (Spanish land registry): `SigpacService` builds visor URLs and fetches GeoJSON via `sigpac-hubcloud.es`. Requires `provincia_cod`, `municipio_cod`, `poligono`, and `parcela_sigpac` on the parcela.
- **AEMET** (meteorological agency): `AemetClient` talks to `opendata.aemet.es` (two-step API, ISO-8859-1 payloads; needs `AEMET_API_KEY`). `ImportadorMeteorologico` imports observed daily data per station (`DatoMeteorologico`, published with ~4 days of lag) and the 7-day municipal forecast (`PrediccionMeteorologica`, keyed by INE code `PPMMM` = `Finca::codigo_ine`). AEMET stations are shared between fincas: users cannot write or delete their data, only data of their own manual stations.

- **Registro de Productos Fitosanitarios** (MAPA, REGFIWEB): `ImportadorFitosanitarios` POSTs to `Exportaciones/ExportJsonProductos` (JSON double-wrapped as a string, `Contenido` is itself JSON; the full export is ~10 MB and the server can be slow, hence the 600 s timeout) and upserts vigentes into `productos_fitosanitarios` by `mapa_id`; products that disappear are kept as `vigente = false`. Extra requests filtered by `idCultivo` fill `cultivos` with the keys of `CULTIVOS_REGISTRO` (vid, olivo, pistacho and the herbáceos: trigo, cebada, girasol…). `ImportadorFitosanitarios::cultivoRegistroDe($parcela)` gives a parcela's key: from `uso`, and for Secano from the variedad name (null = unknown, no filtering). `unidad` (l or kg) is deduced from the formulation code; `dosis_l_ha` holds the dose in that unit per ha and `tratamientos.unidad` keeps the unit used. The export has no doses or safety periods: `LectorFichasFitosanitarios` reads the official safety periods per crop from each product's PDF ficha (`smalot/pdfparser`, table «USO / P.S. (días)») into `plazos_seguridad_productos` (`dias` null = NP); doses are not parsed (unreliable text order, mixed units). A treatment's `plazo_seguridad_dias` is what the farmer entered, else the official one for the parcela's crop, else the product's own. Rows with `user_id` are a user's own products (`ProductoFitosanitarioPolicy`); registry rows are read-only.
- **Tratamientos**: farmers usually treat a whole finca at once: `tratamientos.finca.create/store` takes one application and `TratamientoService::registrarEnParcelas` creates one `Tratamiento` per selected parcela (the CUE is kept per SIGPAC parcel), rejecting parcelas whose crop the product is not authorised for. Each treatment stores what Reg. (UE) 2023/564 and Orden APA/204/2023 require: `hora_inicio`, `cultivo_eppo` (set by the app from `ImportadorFitosanitarios::codigoEppoDe($parcela)`, never from the form), `bbch` (if empty, `TratamientoService::bbchObservado` takes each parcela's latest phenological observation of the previous 21 days), `justificacion`, aplicador/asesor NIF and ROPO, `asesor_fecha_validacion` and `equipo_inspeccion_fecha` (ITEAF, valid `Tratamiento::VIGENCIA_INSPECCION_EQUIPO_ANIOS`); `CuadernoCampana` warns about each missing one. `tratamientos.edit/update` correct a single treatment (`TratamientoService::actualizar` recomputes its cost and its keyed alerts). Prices are per user (`PrecioProductoFitosanitario`, € per l or kg, also for shared registry products); a treatment with `precio_unitario` stores it as the user's price and creates a `Coste` (category «Productos fitosanitarios», `costes.tratamiento_id`, deleted with the treatment) of dose × treated area × price.
- **API**: every `/api/parcelas/{parcela}/…` endpoint authorises against `$parcela->finca`; `fincas.parcelas` uses scoped bindings. Weather stations are read-only through the API (AEMET ones are shared; manual ones only visible to users with a finca linked to them).

## Scheduled tasks

Defined in `routes/console.php`, run by the `scheduler` container (Europe/Madrid times): `fitosanitarios:importar` on Mondays at 05:30, `fitosanitarios:fichas` daily at 05:45 (150 PDF fichas per run, products already used first, re-read after 60 days), `aemet:sincronizar` at 06:30, `grados-dia:recalcular` at 06:50, `alertas:generar` at 07:00 (end of safety periods, observed and forecast frosts, downy mildew "3-10" rule), then `alertas:enviar` (email digest) at 07:10. Alert generators are idempotent through the unique `alertas.clave` column. A separate `db-backup` container dumps the database to `backups/` daily.
- **Cuaderno de explotación (CUE)**: `CuadernoCampana` assembles a finca's campaign (Orden APA/204/2023 sections: general data and plots, phytosanitary treatments, fertilisation, harvest, irrigation) plus compliance warnings; `ExportadorCUE` writes it to .xlsx (PhpSpreadsheet) and `cuaderno/imprimir` is the printable/PDF version. Electronic submission to SIEX is only possible for MAPA-authorised entities, so there is no XML export. Irrigation records (`Riego`, module Riegos) are only allowed on non-`Secano` parcels (`Parcela::regable()`). The old `cuaderno_entradas` table/`CuadernoEntrada` model are unused.
