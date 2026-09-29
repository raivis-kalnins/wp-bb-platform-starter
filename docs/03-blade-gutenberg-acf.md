# Blade + Gutenberg + ACF

This starter does not force an immediate Sage rewrite. Existing PHP and block-template behavior remains valid.

The parent theme registers `resources/views` with Acorn when available and provides `wp_bb_blade()` as a safe renderer with PHP fallback.

BBuilder's ACF Hero/Gallery registration now calls a renderer that:

1. gathers ACF field values;
2. tries the corresponding parent/child Blade view;
3. falls back to the original PHP render template if Blade is missing or fails.

This pattern should be used when migrating additional blocks.

## Recommended block structure

```text
block.json
Block.php              optional controller/data mapper
fields.php             ACF field definition when needed
resources/views/blocks/example.blade.php
render.php             legacy fallback during transition
```

Use Gutenberg native attributes for simple blocks, ACF for editorial field-heavy blocks, and custom tables/services for application data.
