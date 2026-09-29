# Sector child themes

All supplied sector child-theme archives are preserved under `packages/child-themes/`.

Install one with:

```bash
bin/install-sector medicine
```

The current child themes still contain some domain/business code inherited from the existing suite. They are kept intact for compatibility in v0.1.

For the next cleanup phase, move critical content types and business behavior out of child themes into domain plugins. In particular, Medicine/Pharmacy/Jobs/Booking should become separate plugins so switching presentation cannot disable business functionality.

The versioned patch files found in some child themes should also be consolidated into current source files after regression tests are in place. Git should preserve history instead of production themes loading a chain of `v100`, `v120`, `v140` patch files.
