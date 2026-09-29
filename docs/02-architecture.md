# Architecture

```text
Bedrock project
  |
  +-- web/wp                  WordPress core managed by Composer
  +-- web/app/plugins         Composer/local WordPress plugins
  +-- web/app/mu-plugins      always-on platform layer
  +-- web/app/themes          parent + active child theme
  +-- packages                source for your local packages
  +-- config                  environment-aware WordPress config
  +-- docker                  local PHP/Apache toolchain
```

Acorn is required at project level rather than hidden inside one theme. The WP BB Platform MU plugin boots it early. This means domain plugins can use Laravel components even if presentation changes.

## Responsibilities

- **WP BBTheme:** visual shell, design tokens, template/view helpers, shared presentation.
- **Child theme:** sector branding, layout overrides, patterns and demo presentation.
- **BBuilder:** Gutenberg/ACF content-building experience.
- **Woo Support:** reusable WooCommerce storefront/business features.
- **WP BB Platform:** infrastructure, health, operational tooling and Acorn bootstrap.
- **Domain plugins:** warehouse, medicine, pharmacy, jobs, bookings, integrations and custom tables.

Avoid registering critical business entities only inside a child theme. Theme changes must not destroy the application's data model or admin capabilities.
