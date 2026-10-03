# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [Unreleased]

## [1.2.0] - 2026-10-03

### Added
- [lib/bonsai-hub/] Bundled Bonsai Hub 1.0.0: a shared top-level **Bonsai** admin menu with a left-hand nav for every Bonsai plugin, plus a **Plugins** screen to install, activate and deactivate the rest of the suite from GitHub releases.

### Changed
- [bonsai-page-transitions.php] Settings moved from **Settings → Page Transitions** to **Bonsai → Page Transitions** (`admin.php?page=bonsai-page-transitions`), registered through the `bonsai_hub_modules` filter. Old `options-general.php` links redirect. No option or field changes.
- [README.md] Update checker repo corrected to `Bonsai-Systems/bonsai-page-transitions`.

### Removed
- [includes/admin-ui.php, assets/bonsai-admin-ui.css, assets/bonsai-avatar.jpg] Per-plugin header and design-system copy. The hub now provides both.

## [1.1.0] - 2026-09-30

### Changed
- [includes/admin-ui.php, assets/] Settings → Page Transitions restyled with the Bonsai admin design system: logo header with version and GitHub/changelog links, settings in a card. Stylesheet loads on this screen only. No option or field changes.

### Fixed
- [bonsai-page-transitions.php] Sites using the `bonsai_page_transitions_capability` filter could open the settings page but not save it, because `options.php` still required `manage_options`. Added `option_page_capability_bpt_settings_group`.
- [bonsai-page-transitions.php] Transition style and overlay colour labels weren't tied to their inputs; added `label_for`.

## [1.0.2] - 2026-07-30

### Fixed
- [assets/js/page-transition.js] Respect `e.isDefaultPrevented()` on link clicks — skip the transition when another handler (mega menu, accordion, modal trigger) has already cancelled the click on an `<a>` that isn't actually navigating

## [1.0.1] - 2026-07-29

### Fixed
- [assets/css/page-transition.css] Added `scrollbar-gutter: stable` on `html` to stop the page jumping sideways when the scrollbar reappears after the entrance overlay clears `overflow: hidden` on `body`

## [1.0.0] - 2026-07-29

### Added
- [bonsai-page-transitions.php] Initial release, extracted from a site-specific implementation on the Farani Taylor theme: Settings page with Transition Style (None/Fade/Slide Up/Curtain), Overlay Colour and Skip Homepage fields
- [bonsai-page-transitions.php] Overlay markup output via `wp_body_open`, styles/script enqueued via `wp_enqueue_scripts`, both gated by `bpt_is_active()`
- [assets/css/page-transition.css, assets/js/page-transition.js] Fade / Slide Up / Curtain animations — CSS-only entrance (no flash while JS loads), JS-driven exit on internal link click before the real navigation fires
- [bonsai-page-transitions.php] Capability gate (`manage_options`, filterable via `bonsai_page_transitions_capability`) on settings page and save handlers
- [bonsai-page-transitions.php] Settings link added to the plugin's row on the Plugins screen
- [bonsai-page-transitions.php] Options deleted on uninstall
- [composer.json, vendor/] Wired up YahnisElsts/plugin-update-checker (^5.6) so the plugin can self-update from GitHub releases via the wp-admin Plugins screen, matching `bonsai-code-injector`
