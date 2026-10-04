# agriculNet — Programa Agrario

Aplicación web para gestionar explotaciones agrícolas en España: fincas y parcelas (con referencia SIGPAC), tratamientos fitosanitarios, fertilización, riegos, cosecha, costes, calendario fenológico de la vid, datos y previsión meteorológica de AEMET, alertas automáticas y el **cuaderno de explotación** que exige la normativa (Orden APA/204/2023).

Pensada para viñedo, pero admite también olivar, pistacho y cultivos herbáceos de secano.

## Módulos

| Módulo | Qué hace |
|---|---|
| **Viñedo** (fincas y parcelas) | Fincas con titular, NIF y nº REA; parcelas con referencia SIGPAC, uso (Secano, Viña en espaldera, Viña en vaso, Olivar, Pistachos), variedad o cultivo y superficie. El selector de variedad se limita al cultivo del uso (vid, olivo, pistacho, herbáceos). La finca se ubica con la geometría SIGPAC de sus parcelas. |
| **Fenología** | Observaciones BBCH de la vid y **calendario de campaña**: bandas de fase por parcela, tratamientos y heladas marcados, y referencia de lo habitual según la época de maduración de la variedad (adelantada / en fecha / retrasada). Solo parcelas de viña. |
| **Meteorología** | Datos diarios observados de la estación AEMET vinculada y **previsión de 7 días** por municipio. El selector de estación ordena las más cercanas a la finca, de cualquier provincia. |
| **Tratamientos** | Tratamientos fitosanitarios con los datos del cuaderno: producto y nº de registro, dosis, superficie tratada, aplicador (nº ROPO), equipo (nº ROMA) y eficacia. Generan alertas de plazo de seguridad y de dosis excedida. |
| **Riegos** | Riegos por parcela (volumen, superficie, duración, sistema, origen del agua), con dosis en m³/ha y mm comparada con la lluvia de la campaña. Las parcelas de secano no se riegan. |
| **Costes** | Costes por parcela y categoría, con resumen anual. |
| **Cuaderno de explotación** | Por finca y campaña: datos generales y parcelas, tratamientos, fertilización, cosecha y riego. Avisa de lo que falta para cumplir la normativa. Se descarga en **Excel** (una hoja por sección) o en versión **imprimible / PDF**. |
| **Alertas** | Plazo de seguridad y dosis excedida al registrar tratamientos; cada día, fin de plazo de seguridad, heladas observadas y previstas, y riesgo de mildiu (regla de los tres dieces). Contador de no leídas en el menú. |

> **Cuaderno digital y SIEX.** Desde 2026 la fertilización debe anotarse en el cuaderno en el plazo de un mes, y desde el 1 de enero de 2027 el registro electrónico de tratamientos es obligatorio. El envío a SIEX (MAPA) solo pueden hacerlo las entidades habilitadas, por lo que la aplicación no genera ningún XML: el Excel y la versión impresa sirven para llevar el cuaderno al día y entregarlo a quien lo presente, o para pasarlo a la aplicación oficial de la comunidad autónoma.

## Puesta en marcha

Requisitos: Docker Desktop y Node.js (solo para compilar los estilos).

```bash
cp .env.example .env          # y rellena AEMET_API_KEY (alta gratuita en opendata.aemet.es)
docker compose up -d          # nginx, php-fpm, postgres, redis, queue-worker, scheduler, db-backup
docker compose exec php-fpm php artisan key:generate
docker compose exec php-fpm php artisan migrate --seed
docker compose exec php-fpm php artisan aemet:importar-estaciones
npm install && npm run build
```

La aplicación queda en <http://localhost:8080>. El seeder crea el usuario `test@example.com` con contraseña `password`.

`docker-compose.yml` fija el nombre del proyecto (`name: programa_agrario`): no hace falta `-p` y no se levantan stacks duplicados aunque la carpeta tenga otro nombre.

`php-fpm`, `queue-worker` y `scheduler` comparten el volumen `vendor` del contenedor. Las dependencias de PHP se instalan con `docker compose exec php-fpm composer install`; el `vendor/` del equipo solo lo usa el editor.

## Tareas programadas

Las ejecuta el contenedor `scheduler` (hora peninsular):

| Hora | Comando | Qué hace |
|---|---|---|
| 06:30 | `aemet:sincronizar` | Previsión de los municipios con fincas y datos observados de los últimos 10 días de sus estaciones (AEMET publica con unos 4 días de retraso). |
| 07:00 | `alertas:generar` | Alertas diarias. Es idempotente: cada alerta lleva una clave única y repetir la ejecución no duplica nada. |

Se pueden lanzar a mano con `docker compose exec php-fpm php artisan <comando>`.

## Copias de seguridad

El contenedor `db-backup` hace un `pg_dump` comprimido en `backups/` cuando la última copia tiene más de 23 horas (funciona aunque el equipo esté apagado a la hora de la copia). Conserva 14 días y nunca menos de 7 copias. La carpeta `backups/` no se sube al repositorio.

Restaurar (sobrescribe la base de datos):

```bash
gunzip -c backups/agrario_AAAA-MM-DD_HHMM.sql.gz | docker compose exec -T postgres psql -U agrario -d agrario
```

## Tests

```bash
docker compose exec php-fpm php artisan test
```

Los tests usan la base de datos `agrario_test`, caché y colas en memoria, y no pueden llamar a AEMET ni a SIGPAC (hay que simularlos con `Http::fake()`). `tests/TestCase.php` aborta si detecta la base de datos o el Redis de desarrollo, para que `RefreshDatabase` nunca pueda borrar datos reales.

## Arquitectura

Laravel 13 + Livewire/Volt + Tailwind, PostgreSQL 16 y Redis 7. El código de dominio vive en `app/Modules/<Módulo>/` (modelos, servicios, controladores web y API, rutas); `ModulesServiceProvider` carga las rutas de cada módulo. Más detalle técnico en [CLAUDE.md](CLAUDE.md).

## Pendiente

- **Catálogo de productos fitosanitarios**: está vacío y sin él no se pueden registrar tratamientos. Hay que importar el Registro Oficial de Productos Fitosanitarios del MAPA.
- **Usuarios**: perfil, varios usuarios por explotación y roles (por definir).
- **Editar registros**: tratamientos, fertilizaciones, cosechas, riegos y costes solo se crean y se borran.
- **Grados-día**: `RecalcularGradosDia` está sin implementar.
- **Olivar y pistacho**: calendario fenológico y alertas propias.
- **Mildiu con previsión**: usar la previsión horaria de AEMET para avisar antes.
- **Correo**: `MAIL_HOST=mailpit` no existe en el compose; no se envían correos (alertas ni recuperación de contraseña).
- **Limpieza**: la tabla `cuaderno_entradas` y su modelo no se usan.
