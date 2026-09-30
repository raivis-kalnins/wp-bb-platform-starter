#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/../.."

echo "[1/7] Redis container status"
docker compose up -d redis

echo "[2/7] Redis ping"
docker compose exec -T redis redis-cli ping

echo "[3/7] Stop WordPress while cache is reset"
docker compose stop app

echo "[4/7] Clear Redis database 0 (cache only)"
docker compose exec -T redis redis-cli -n 0 FLUSHDB

echo "[5/7] Remove stale WordPress Redis drop-in"
if [ -e web/app/object-cache.php ]; then
  mv web/app/object-cache.php web/app/object-cache.php.stale-$(date +%Y%m%d%H%M%S)
fi

echo "[6/7] Start WordPress and install a fresh drop-in"
docker compose up -d app
sleep 2
./bin/wp redis enable || true

echo "[7/7] Redis status"
./bin/wp redis status || true

echo "Done. If WordPress still cannot reach Redis, set WP_REDIS_DISABLED='true' in .env and restart app."
