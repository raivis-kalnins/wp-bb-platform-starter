# ACF Pro

The original development Docker archive contained ACF Pro. The commercial package is not redistributed in this starter.

If you have a licensed ACF Pro ZIP:

```bash
bin/install-acf-pro ~/Downloads/advanced-custom-fields-pro.zip
```

For a fresh Docker bootstrap, the installer also detects this ignored local artifact automatically:

```text
packages/private/advanced-custom-fields-pro.zip
```

For CI/production, prefer a licensed private Composer source or another controlled artifact repository so the package is reproducibly installed without committing paid plugin code into a public repository.
