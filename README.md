# Visual Site Studio

Commercial WordPress plugin: inspect any front-end element, restyle it without touching theme files, give clients a white-label dashboard, and drop in scheduled dynamic content.

## Why this is sellable

Site owners already pay $49–$199/year for YellowPencil-style visual CSS and Ultimate Dashboard-style client admin. This plugin combines both, plus timed content snippets, in one GPL product you can sell as **support + updates**.

## Features

- Visual editor panel (hide, color, type, spacing, custom CSS)
- Unique selectors and overlay highlighting — page elements are not polluted with helper classes
- Save via authenticated REST API; styles load for every visitor
- Undo / redo, entire-site or this-page scope
- Role gate (administrator / editor / author)
- White-label admin bar name and optional WordPress logo hide
- Multiple client dashboard widgets
- `[vss_content slug="announcement"]` with start/end dates and logged-in visibility
- Gutenberg block for the same snippets
- JSON export / import for agency presets

## How to use

1. Activate the plugin.
2. In wp-admin open **Site Studio** and optionally set brand name + widgets.
3. Visit the site front end while logged in.
4. Click **Studio** (or admin bar → Toggle visual editor).
5. Click an element, change styles, **Save styles**.

## Shortcodes

```
[vss_content slug="announcement"]
```

The old `[custom_content]` shortcode still works if you had the previous Custom Content Display option saved.

## Development notes

PHP 7.4+, WordPress 6.0+. No build step — vanilla JS so agencies can drop it into any site.

Sanitizer unit tests:

```
php tests/test-sanitizer.php
```
