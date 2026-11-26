#!/bin/sh
# Container entrypoint: caches configuration, applies pending migrations and starts the web server.
set -eu
cd /app

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY não definida. Cadastre-a nas variáveis de ambiente do serviço." >&2
    exit 1
fi

# Configuration comes from the runtime environment, so caches are built here and not in the image.
php artisan optimize --no-interaction

# Only pending migrations run; existing data is never dropped. Retries cover a sleeping Neon compute.
attempt=1
until php artisan migrate --force --no-interaction; do
    if [ "$attempt" -ge 5 ]; then
        echo "Migrations falharam após $attempt tentativas." >&2
        exit 1
    fi
    attempt=$((attempt + 1))
    echo "Banco indisponível ou migration falhou; nova tentativa em 5 s ($attempt/5)..." >&2
    sleep 5
done

exec frankenphp run --config /etc/frankenphp/Caddyfile
