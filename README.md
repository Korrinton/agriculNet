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
docker compose up -d          # nginx, php-fpm, postgres, redis, queue-worker, scheduler, db-backup, mailpit
docker compose exec php-fpm php artisan key:generate
docker compose exec php-fpm php artisan migrate --seed
docker compose exec php-fpm php artisan aemet:importar-estaciones
npm install && npm run build
```

La aplicación queda en <http://localhost:8080>. El seeder crea el usuario `test@example.com` con contraseña `password`.

Los correos que envía la aplicación (recuperación de contraseña y resumen diario de alertas) no salen a Internet en desarrollo: los captura Mailpit y se leen en <http://localhost:8025>.

`docker-compose.yml` fija el nombre del proyecto (`name: programa_agrario`): no hace falta `-p` y no se levantan stacks duplicados aunque la carpeta tenga otro nombre.

`php-fpm`, `queue-worker` y `scheduler` comparten el volumen `vendor` del contenedor. Las dependencias de PHP se instalan con `docker compose exec php-fpm composer install`; el `vendor/` del equipo solo lo usa el editor.

## Tareas programadas

Las ejecuta el contenedor `scheduler` (hora peninsular):

| Hora | Comando | Qué hace |
|---|---|---|
| Lunes 05:30 | `fitosanitarios:importar` | Sincroniza el catálogo con el Registro Oficial de Productos Fitosanitarios del MAPA. |
| 05:45 | `fitosanitarios:fichas --limite=150` | Lee los plazos de seguridad oficiales de las fichas PDF de los productos. |
| 06:30 | `aemet:sincronizar` | Previsión de los municipios con fincas y datos observados de los últimos 10 días de sus estaciones (AEMET publica con unos 4 días de retraso). |
| 06:50 | `grados-dia:recalcular` | Grados-día acumulados de la campaña de cada viña. |
| 07:00 | `alertas:generar` | Alertas diarias. Es idempotente: cada alerta lleva una clave única y repetir la ejecución no duplica nada. |
| 07:10 | `alertas:enviar` | Un correo por usuario con sus alertas nuevas (no leídas, no enviadas y de los últimos 3 días), según lo que elija en su perfil: todas, avisos e importantes, solo importantes o ninguna. Cada correo lleva un enlace para darse de baja sin iniciar sesión. |

Se pueden lanzar a mano con `docker compose exec php-fpm php artisan <comando>` o desde el backoffice, donde se ve además el historial de cada una.

## Backoffice

En `/admin` (menú del usuario → «Administración»), solo para administradores:

```bash
docker compose exec php-fpm php artisan usuarios:admin tu@email.com            # dar acceso
docker compose exec php-fpm php artisan usuarios:admin tu@email.com --quitar   # quitarlo
```

- **Panel**: usuarios activos, superficie por cultivo, tratamientos y altas por mes, registros del año y aviso de tareas que han fallado.
- **Usuarios**: búsqueda, último acceso y uso de cada cuenta; bloquear, desbloquear y borrar (pide escribir el email). Cada acción queda en el log.
- **Tareas**: estado, historial y salida de cada tarea programada, y botón para ejecutarla ahora (va a la cola).
- **Catálogos**: variedades y categorías de coste (editables; no se borra lo que está en uso), estaciones meteorológicas y productos fitosanitarios (solo consulta: vienen de AEMET y del MAPA).

El backoffice muestra datos de las cuentas y cifras agregadas, no el contenido de las explotaciones de cada usuario.

## Copias de seguridad

El contenedor `db-backup` hace un `pg_dump` comprimido cuando la última copia tiene más de 23 horas (funciona aunque el equipo esté apagado a la hora de la copia). Conserva 14 días y nunca menos de 7 copias. En `.env`:

- `BACKUP_DIR`: carpeta del equipo donde se guardan (por defecto `./backups`, que no se sube al repositorio). Las copias llevan los datos y NIF de todos los usuarios: mejor fuera de carpetas sincronizadas con la nube como OneDrive.
- `BACKUP_PASSPHRASE`: si tiene valor, cada copia se cifra con AES-256 (`.sql.gz.enc`). Obligatoria en producción. Guárdala en un gestor de contraseñas: sin ella no se puede restaurar.

Restaurar (sobrescribe la base de datos; `openssl` viene con Git Bash):

```bash
# Copia cifrada (pide la frase)
openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -in backups/agrario_AAAA-MM-DD_HHMM.sql.gz.enc \
  | gunzip | docker compose exec -T postgres psql -U agrario -d agrario

# Copia sin cifrar
gunzip -c backups/agrario_AAAA-MM-DD_HHMM.sql.gz | docker compose exec -T postgres psql -U agrario -d agrario
```

## Producción

`docker-compose.prod.yml` se superpone al de desarrollo: nginx con HTTPS (HTTP redirige a HTTPS, HSTS), PostgreSQL y Redis sin puertos publicados y copias cifradas obligatorias. Las cabeceras de seguridad (`docker/nginx/cabeceras-seguridad.conf`) se aplican también en desarrollo.

1. En el servidor, `.env` a partir de `.env.production.example`, rellenando lo marcado con `RELLENAR`.
2. Certificado de Let's Encrypt (con el puerto 80 libre, antes del primer arranque) y copia a `CERTS_DIR`:
   ```bash
   docker run --rm -p 80:80 -v /etc/letsencrypt:/etc/letsencrypt certbot/certbot certonly --standalone -d dominio.es
   mkdir -p /srv/agriculnet/certs && cp -L /etc/letsencrypt/live/dominio.es/{fullchain,privkey}.pem /srv/agriculnet/certs/
   ```
3. Arrancar (el `--build` es necesario: el arranque de PHP va dentro de la imagen):
   ```bash
   docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
   ```
   Antes de arrancar, php-fpm ejecuta `php artisan seguridad:comprobar` y **no arranca** si hay depuración activa, HTTP en vez de HTTPS, cookie de sesión no segura, contraseña de BD de ejemplo, copias sin cifrar o el usuario de pruebas `test@example.com`. Se puede ejecutar a mano en cualquier momento.
4. Correo: `MAIL_*` con el SMTP de un proveedor (el del dominio, o uno transaccional como Brevo o Amazon SES, con servidores en la UE). Para que no acabe en spam, configura en el DNS del dominio los registros SPF y DKIM que indique el proveedor. Prueba: «¿Olvidaste tu contraseña?» con tu email.
5. Nombrar al administrador: `docker compose exec php-fpm php artisan usuarios:admin tu@email.com`.
6. Renovación del certificado (cron diario):
   ```bash
   docker run --rm -v /etc/letsencrypt:/etc/letsencrypt -v "$PWD/docker/certbot/www:/var/www/certbot" certbot/certbot renew \
     --webroot -w /var/www/certbot --quiet \
     --deploy-hook "cp -L /etc/letsencrypt/live/dominio.es/fullchain.pem /etc/letsencrypt/live/dominio.es/privkey.pem /srv/agriculnet/certs/" \
   && docker compose exec nginx nginx -s reload
   ```

## Tests

```bash
docker compose exec php-fpm php artisan test
```

Los tests usan la base de datos `agrario_test`, caché y colas en memoria, y no pueden llamar a AEMET ni a SIGPAC (hay que simularlos con `Http::fake()`). `tests/TestCase.php` aborta si detecta la base de datos o el Redis de desarrollo, para que `RefreshDatabase` nunca pueda borrar datos reales.

## Arquitectura

Laravel 13 + Livewire/Volt + Tailwind, PostgreSQL 16 y Redis 7. El código de dominio vive en `app/Modules/<Módulo>/` (modelos, servicios, controladores web y API, rutas); `ModulesServiceProvider` carga las rutas de cada módulo. Más detalle técnico en [CLAUDE.md](CLAUDE.md).

## Pendiente

- **Usuarios**: perfil, varios usuarios por explotación y roles (por definir).
- **Editar registros**: los tratamientos ya se pueden corregir; fertilizaciones, cosechas, riegos y costes solo se crean y se borran.
- **Olivar y pistacho**: calendario fenológico y alertas propias.
- **Mildiu con previsión**: usar la previsión horaria de AEMET para avisar antes.
- **Limpieza**: la tabla `cuaderno_entradas` y su modelo no se usan.

### Cuaderno oficial: lo que falta para pasar una inspección

Desde el **1 de enero de 2027** el registro de tratamientos fitosanitarios tiene que ser electrónico y legible por máquina, anotado como máximo **30 días** después de aplicarlo, y se conserva con sus documentos **al menos 3 años** (Reglamento de Ejecución (UE) 2023/564, RD 1311/2012 art. 16, Orden APA/204/2023 anexo II). Las hojas de cálculo dejan de valer y lo que cuenta ante un inspector es el cuaderno conectado al SIEX; agriculNet no puede enviar datos al SIEX (solo las entidades autorizadas por el MAPA), así que los tratamientos habrá que pasarlos a la aplicación oficial gratuita (MAPA o Castilla-La Mancha) dentro de esos 30 días.

Antes de empezar, preguntar en la oficina comarcal agraria o la cooperativa si la explotación está exenta de asesor y qué herramienta oficial usa Castilla-La Mancha: eso cambia qué campos son obligatorios.

1. ~~**Campos obligatorios de cada tratamiento**~~ — hecho: hora de inicio, cultivo con código EPPO, estadio BBCH (propuesto de la fenología), justificación, NIF del aplicador, equipo ROMA/REGANIP con su inspección ITEAF y asesor (nombre, NIF, ROPO y fecha de validación), en el formulario, el Excel, el imprimible y los avisos del cuaderno. Queda confirmar con el formulario oficial el orden exacto de las columnas.
2. **Integridad del registro**:
   - Tratamientos, fertilizaciones, cosechas y riegos se borran del todo: cambiar a **anulación con motivo**, visible en el historial y conservada 3 años.
   - **Historial de cambios** de las ediciones (quién, cuándo, valor anterior).
   - Aviso de **anotación tardía** (más de 30 días) también para tratamientos; hoy solo existe para fertilización.
3. **Bloques del cuaderno que no existen**:
   - Medidas preventivas o culturales y valoración del cumplimiento de la gestión integrada de plagas (a nivel de explotación).
   - **Semilla tratada** (lote, cantidad, producto y nº de registro): afecta a los herbáceos de secano.
   - Tratamientos postcosecha y de naves o instalaciones, si se hacen.
   - Adjuntar el **plan de abonado** (RD 1051/2022) cuando la explotación esté obligada a tenerlo (comprobar).
4. **Documentos a conservar 3 años**, adjuntos a la finca o al tratamiento: facturas de compra de fitosanitarios, certificado ITEAF, carné ROPO, contrato y recomendaciones del asesor.

### Protección de datos (RGPD y LOPDGDD)

La app guarda datos personales de los usuarios (nombre, email) y de terceros que exige el cuaderno oficial (nombre y NIF del titular, aplicadores, asesores y destinatarios de la cosecha; base legal: obligación legal, art. 6.1.c RGPD).

1. ~~**Derecho de supresión** (art. 17)~~ — hecho: «Eliminar cuenta» fallaba con cualquier usuario con registros; ahora `BorradoCuenta` borra fincas, parcelas, registros, productos propios y estaciones manuales, y avisa de que el registro de tratamientos debe conservarse 3 años (descargar antes el cuaderno).
2. **Informar** (arts. 13 y 14): política de privacidad y aviso legal (LSSI), enlazados desde el registro y el pie; explicar también el tratamiento de los datos de terceros y la retención de 14 días de las copias de seguridad. Borrador a revisar con alguien de protección de datos.
3. ~~**Acceso y portabilidad** (arts. 15 y 20)~~ — hecho: «Perfil → Descargar mis datos» da un .zip con `datos.json` y un CSV por tabla (cuenta, fincas, parcelas, todos los registros, costes, alertas, grados-día, productos y precios propios, estación manual y sus datos). Un test falla si aparece una tabla con `user_id`, `finca_id` o `parcela_id` que no esté en la descarga.
4. ~~**Seguridad** (art. 32)~~ — preparado (ver «Producción»): copias cifradas con `BACKUP_PASSPHRASE` y carpeta configurable (`BACKUP_DIR`), HTTPS con HSTS, cabeceras de seguridad, PostgreSQL y Redis solo accesibles desde el propio equipo (antes, desde toda la red local), plantilla `.env.production.example` y `seguridad:comprobar`, que impide arrancar en producción con una configuración insegura **o con el usuario de pruebas `test@example.com`** (administrador del backoffice: borrarlo y nombrar al administrador real con `php artisan usuarios:admin`). Queda:
   - En desarrollo las copias siguen sin cifrar y dentro de OneDrive (datos de prueba): poner `BACKUP_PASSPHRASE` y un `BACKUP_DIR` fuera de OneDrive antes de meter datos reales.
   - ~~Content-Security-Policy~~ — hecho: solo se ejecuta el JavaScript de la propia aplicación (sin `<script>` ni `onclick` en las vistas; un test lo vigila). Queda `'unsafe-eval'`, que Livewire 3 y Alpine necesitan para sus expresiones. Leaflet ya no se carga de unpkg.com.
5. **Si la usan otros agricultores**: para sus datos la app actúa como encargado del tratamiento → contrato de encargo (art. 28) en las condiciones de uso y registro de actividades de tratamiento (art. 30).
