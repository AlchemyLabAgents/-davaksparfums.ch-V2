# Davaks Parfums (WordPress)

## Overview
This repository contains the WordPress site for davaksparfums.ch plus custom static landing pages used for SEO/AE/O.

## Structure
- domains/davaksparfums.ch/
  - home-90-german-seo.html (source landing page)
  - public_html/ (WordPress web root)
    - home-90-german-seo.html (deployed landing page)
    - wp-content/ (themes, plugins, uploads)
    - wp-config.php (site configuration)

## Key Customizations
- Astra child theme styles:
  - domains/davaksparfums.ch/public_html/wp-content/themes/astra-child/style.css
- Landing page content:
  - domains/davaksparfums.ch/home-90-german-seo.html
  - domains/davaksparfums.ch/public_html/home-90-german-seo.html

## Git Hygiene
To keep the repository clean:
- WordPress core, default themes, language packs, and default plugins are ignored.
- Secrets are stored in domains/davaksparfums.ch/.env (not tracked).

## Maintenance (WP-CLI)
Run commands from any path, always pass the site root:

- Cache flush:
  - wp cache flush --path=/home/u633076877/domains/davaksparfums.ch/public_html
- Clear transients:
  - wp transient delete --all --path=/home/u633076877/domains/davaksparfums.ch/public_html
- Verify installation:
  - wp core is-installed --path=/home/u633076877/domains/davaksparfums.ch/public_html

## Audit Summary (2026-01-18)
- Normalized section spacing on the home landing page.
- Restored WordPress core files in de_DE locale.
- Flushed WP cache and cleared transients.
- Updated .gitignore to exclude WordPress core/default assets and language packs.
- Found wp-content/debug.log (71K); monitor/rotate if needed.

## Deployment
Pushes to main are assumed to be deployed via the hosting workflow. Ensure public_html/home-90-german-seo.html is kept in sync with the source landing page file.
