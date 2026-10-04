#!/bin/sh
# Copia de seguridad diaria de PostgreSQL.
#
# Cada hora comprueba si la última copia tiene más de BACKUP_INTERVAL_HOURS horas
# y, si es así (o no hay ninguna), hace un pg_dump comprimido. Así funciona aunque
# el equipo esté apagado a la hora en que tocaría la copia.
#
# Retención: borra copias con más de BACKUP_KEEP_DAYS días, pero nunca deja
# menos de BACKUP_KEEP_MIN copias.
#
# Restaurar (¡sobrescribe la BD!):
#   gunzip -c backups/agrario_AAAA-MM-DD_HHMM.sql.gz | docker compose exec -T postgres psql -U agrario -d agrario
set -eu

DIR=/backups
INTERVAL_HOURS="${BACKUP_INTERVAL_HOURS:-23}"
KEEP_DAYS="${BACKUP_KEEP_DAYS:-14}"
KEEP_MIN="${BACKUP_KEEP_MIN:-7}"

log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"; }

hacer_copia() {
    destino="$DIR/${PGDATABASE}_$(date '+%Y-%m-%d_%H%M').sql.gz"
    tmp="$destino.part"
    if pg_dump --clean --if-exists --no-owner | gzip -9 > "$tmp"; then
        mv "$tmp" "$destino"
        log "Copia creada: $(basename "$destino") ($(du -h "$destino" | cut -f1))"
    else
        rm -f "$tmp"
        log "ERROR: pg_dump ha fallado"
        return 1
    fi
}

limpiar() {
    total=$(ls -1 "$DIR"/"$PGDATABASE"_*.sql.gz 2>/dev/null | wc -l)
    [ "$total" -le "$KEEP_MIN" ] && return 0
    # Candidatas: las más antiguas que superan el mínimo y además tienen más de KEEP_DAYS días
    ls -1t "$DIR"/"$PGDATABASE"_*.sql.gz | tail -n +"$((KEEP_MIN + 1))" | while read -r f; do
        if [ -n "$(find "$f" -mtime +"$KEEP_DAYS")" ]; then
            rm -f "$f" && log "Borrada copia antigua: $(basename "$f")"
        fi
    done
}

necesita_copia() {
    ultima=$(ls -1t "$DIR"/"$PGDATABASE"_*.sql.gz 2>/dev/null | head -n 1 || true)
    [ -z "$ultima" ] && return 0
    [ -n "$(find "$ultima" -mmin +"$((INTERVAL_HOURS * 60))")" ]
}

mkdir -p "$DIR"
rm -f "$DIR"/*.part
log "Servicio de copias iniciado (cada ${INTERVAL_HOURS}h, retención ${KEEP_DAYS} días, mínimo ${KEEP_MIN} copias)"

while true; do
    if necesita_copia; then
        hacer_copia && limpiar || true
    fi
    sleep 3600
done
