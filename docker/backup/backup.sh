#!/bin/sh
# Copia de seguridad diaria de PostgreSQL.
#
# Cada hora comprueba si la última copia tiene más de BACKUP_INTERVAL_HOURS horas
# y, si es así (o no hay ninguna), hace un pg_dump comprimido. Así funciona aunque
# el equipo esté apagado a la hora en que tocaría la copia.
#
# Cifrado: con BACKUP_PASSPHRASE, la copia se cifra con AES-256 (openssl, clave derivada
# con PBKDF2) y se guarda como .sql.gz.enc. Las copias llevan los NIF y datos de todos los
# usuarios: en producción es obligatorio (BACKUP_REQUIRE_ENCRYPTION=1).
#
# Retención: borra copias con más de BACKUP_KEEP_DAYS días, pero nunca deja
# menos de BACKUP_KEEP_MIN copias.
#
# Restaurar (¡sobrescribe la BD!):
#   cifrada:    openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -in backups/agrario_AAAA-MM-DD_HHMM.sql.gz.enc \
#                 | gunzip | docker compose exec -T postgres psql -U agrario -d agrario
#               (pide la frase de BACKUP_PASSPHRASE)
#   sin cifrar: gunzip -c backups/agrario_AAAA-MM-DD_HHMM.sql.gz | docker compose exec -T postgres psql -U agrario -d agrario
set -eu

DIR=/backups
INTERVAL_HOURS="${BACKUP_INTERVAL_HOURS:-23}"
KEEP_DAYS="${BACKUP_KEEP_DAYS:-14}"
KEEP_MIN="${BACKUP_KEEP_MIN:-7}"
PASSPHRASE="${BACKUP_PASSPHRASE:-}"
ITERACIONES=200000

log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"; }

# Copias de esta base de datos, cifradas o no, de la más reciente a la más antigua
copias() {
    ls -1t "$DIR"/"$PGDATABASE"_*.sql.gz "$DIR"/"$PGDATABASE"_*.sql.gz.enc 2>/dev/null || true
}

hacer_copia() {
    if [ -n "$PASSPHRASE" ]; then
        destino="$DIR/${PGDATABASE}_$(date '+%Y-%m-%d_%H%M').sql.gz.enc"
    else
        destino="$DIR/${PGDATABASE}_$(date '+%Y-%m-%d_%H%M').sql.gz"
    fi
    tmp="$destino.part"

    # pipefail no existe en sh: el estado de pg_dump se guarda aparte
    estado="$DIR/.pg_dump_estado"
    rm -f "$estado"
    if [ -n "$PASSPHRASE" ]; then
        { pg_dump --clean --if-exists --no-owner || echo fallo > "$estado"; } | gzip -9 \
            | openssl enc -aes-256-cbc -pbkdf2 -iter "$ITERACIONES" -salt -pass env:BACKUP_PASSPHRASE > "$tmp"
    else
        { pg_dump --clean --if-exists --no-owner || echo fallo > "$estado"; } | gzip -9 > "$tmp"
    fi

    if [ ! -f "$estado" ] && [ -s "$tmp" ]; then
        mv "$tmp" "$destino"
        log "Copia creada: $(basename "$destino") ($(du -h "$destino" | cut -f1))"
    else
        rm -f "$tmp" "$estado"
        log "ERROR: pg_dump ha fallado"
        return 1
    fi
}

limpiar() {
    total=$(copias | wc -l)
    [ "$total" -le "$KEEP_MIN" ] && return 0
    # Candidatas: las más antiguas que superan el mínimo y además tienen más de KEEP_DAYS días
    copias | tail -n +"$((KEEP_MIN + 1))" | while read -r f; do
        if [ -n "$(find "$f" -mtime +"$KEEP_DAYS")" ]; then
            rm -f "$f" && log "Borrada copia antigua: $(basename "$f")"
        fi
    done
}

necesita_copia() {
    ultima=$(copias | head -n 1)
    [ -z "$ultima" ] && return 0
    [ -n "$(find "$ultima" -mmin +"$((INTERVAL_HOURS * 60))")" ]
}

if [ -z "$PASSPHRASE" ]; then
    if [ "${BACKUP_REQUIRE_ENCRYPTION:-0}" = "1" ]; then
        log "ERROR: falta BACKUP_PASSPHRASE y las copias deben ir cifradas (BACKUP_REQUIRE_ENCRYPTION=1). No se hace ninguna copia."
        exit 1
    fi
    log "AVISO: sin BACKUP_PASSPHRASE las copias se guardan SIN CIFRAR"
fi

mkdir -p "$DIR"
rm -f "$DIR"/*.part
log "Servicio de copias iniciado (cada ${INTERVAL_HOURS}h, retención ${KEEP_DAYS} días, mínimo ${KEEP_MIN} copias, $([ -n "$PASSPHRASE" ] && echo cifradas || echo sin cifrar))"

while true; do
    if necesita_copia; then
        hacer_copia && limpiar || true
    fi
    sleep 3600
done
