#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")"
docker compose up -d
echo "Shared proxy started. Mailpit: http://mailpit.localhost"
