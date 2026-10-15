# WP BB Platform cPanel setup

Reusable cPanel first-install support stored inside `packages/wp-bb-platform/cpanel-setup/` so every project can keep the same deployment workflow in Git.

## Prepare a project

1. Create an empty MySQL/MariaDB database and user in cPanel. Assign **ALL PRIVILEGES**.
2. Keep the domain document root pointed to `PROJECT/web`.
3. For a licensed ACF Pro install, place `advanced-custom-fields-pro.zip` in `cpanel-setup/private/`.
4. Optionally place the project's UpdraftPlus `*-db.gz` backup in `cpanel-setup/updraft-backup/`.
5. Copy `cpanel-setup/setup.php` to `PROJECT/web/setup.php`.
6. Open `/setup.php` once. If no setup key exists, the installer creates `PROJECT/.wpbb-setup-key`; read it with cPanel File Manager and reopen `/setup.php?key=...`.
7. Enter the empty database details, test, save, then install.
8. Open WordPress admin → UpdraftPlus → **Rescan local folder** → restore **Database only**.

The setup locks itself after a successful install. Delete `web/setup.php` afterwards as an additional hardening step.

## Git / ACF Pro

The installer workflow belongs in Git. The licensed ACF Pro ZIP and real database backups do not: `.gitignore` excludes them. This downloadable update may contain your supplied private ACF Pro ZIP and current database backup for your own deployment convenience; do not commit those two binary files to a public repository.

## WordPress admin

The cPanel installer keeps direct PHP file editing disabled but attempts to allow normal plugin/theme install, upload, update, and removal from wp-admin.
