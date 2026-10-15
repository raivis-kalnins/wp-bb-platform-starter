# WP BB Platform changelog

## 0.2.0

- Adds reusable `cpanel-setup/` deployment kit for any Bedrock/Acorn WP BB project.
- Browser-first cPanel setup with generated private setup key, database test, `.env` generation and Composer install.
- Installs a privately supplied licensed ACF Pro ZIP on first cPanel install.
- Copies an UpdraftPlus database backup into `web/app/updraft/` ready for rescan/restore.
- Adds a WordPress Dashboard overview for local traffic and WooCommerce sales/orders.
- Adds **BB Platform → Blade & Laravel** admin tools: Acorn status, allow-listed Blade file editor, cache clear, JSON preview context and sandboxed Blade render preview.
- Keeps commercial ACF Pro ZIPs and project database backups excluded from public Git by default.
