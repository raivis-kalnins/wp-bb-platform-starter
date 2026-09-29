# Operations UI and WP-CLI

The **BB Platform** wp-admin screen provides system health plus allow-listed maintenance operations. Native-safe operations run directly; CLI-backed operations execute only when `WPBB_ALLOW_ADMIN_CLI=true`.

Production recommendation: leave admin CLI execution disabled and run commands over SSH.

Useful commands:

```bash
bin/wp bb health
bin/wp plugin list
bin/wp theme list
bin/wp cron event list
bin/wp redis status
bin/artisan about
bin/artisan migrate:status
```

The UI intentionally has no arbitrary command textbox. If an operation becomes common, add it to the allow-list in the platform plugin so it can be audited and permission-checked.
