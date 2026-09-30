# Windows 11 + WSL2 + Docker installation

## Recommended setup

1. Enable WSL2 and install Ubuntu.
2. Install Docker Desktop and enable integration for the Ubuntu distribution.
3. Keep active projects inside the Linux filesystem, for example `~/projects/wp-bb-platform`.
4. Extract/copy this starter there.
5. Run `./bin/install`. It creates `.env`, installs the locked Composer dependencies, links the tracked project packages, prepares writable runtime directories and installs WordPress.

Project-owned theme and plugin source is versioned under `packages/`. Do not edit the ignored Composer install paths under `web/app/themes` or `web/app/plugins` directly.

The default Docker stack exposes the website on port 8080, phpMyAdmin on 8081 and Mailpit on 8025, so no hosts-file edits are required.

## Shared reverse proxy (optional)

A maintained nginx-proxy stack is included under `docker/rproxy/` for people who prefer `*.localhost` domains.

```bash
cd docker/rproxy
./up.sh
cd ../..
docker compose -f docker-compose.yml -f docker-compose.proxy.yml up -d
```

Then configure `SITE_DOMAIN`/`PMA_DOMAIN` as needed. The simple port-based setup should be used first because it has fewer moving parts.

To make `bin/install`, `bin/wp` and the other Compose-backed helpers consistently use the proxy, uncomment this line in the local `.env` after the proxy network is running:

```dotenv
COMPOSE_FILE='docker-compose.yml:docker-compose.proxy.yml'
```
