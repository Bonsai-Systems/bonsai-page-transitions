# Bonsai Page Transitions

A minimal WordPress plugin that plays a full-screen wipe animation whenever a visitor clicks a link to another page on the site. There's no AJAX content-swap — the browser still does a real navigation, the overlay just covers/reveals it, so it works with any theme, plugin, caching setup or analytics tracking as-is.

## Features

- Settings page under **Settings → Page Transitions**
- Three styles: **Fade**, **Slide Up**, **Curtain** (or **None** to disable)
- Configurable overlay colour
- **Skip Homepage** option, for themes that already have their own homepage loader/intro animation
- Per-link opt-out via a `no-transition` class on the `<a>` tag
- Respects `prefers-reduced-motion` (overlay is removed entirely)
- Access restricted to `manage_options` by default (filterable via `bonsai_page_transitions_capability`)
- Options removed on uninstall

## Requirements

- WordPress 6.0+
- PHP 8.0+
- Theme must call `wp_body_open()` in `header.php`/`site-header.php`, immediately after the opening `<body>` tag. This is WP core best practice since 5.2, and already required by Bonsai Code Injector's Body Code field — Bonsai base themes should call it regardless of whether this plugin is active.

## Usage

1. Activate the plugin.
2. Go to **Settings → Page Transitions**.
3. Pick a style, set the overlay colour, and decide whether the homepage should be skipped.
4. Save. Every internal link on the site now plays the transition on click.

To exclude a specific link (e.g. a booking widget or external-feeling CTA that should feel instant), add `no-transition` to its class list.

## How It Works

- **Entrance** (on page load) runs automatically via a CSS animation on the overlay — no JS required, so there's no flash of covered content while scripts load.
- **Exit** (on internal link click) is triggered by JS: it intercepts the click, adds `.is-leaving` to the overlay (which swaps in the exit keyframes), waits for the animation to finish (or 900ms, whichever comes first, as a safety net), then sets `window.location.href` to the link's original destination.
- Skips anchor links (`#section`), `mailto:`/`tel:`/`javascript:`, `target="_blank"`, `download` links, `wp-admin`/`wp-login.php`, off-site links, modified clicks (cmd/ctrl/shift/middle-click), and anything with a `no-transition` class.
- Clears its own state on `pageshow` with `event.persisted`, so returning via the browser's back/forward button (bfcache) never leaves the overlay stuck covering the page.

## Data Structure

Three options are stored:

- `bpt_transition_style` (string: `none` / `fade` / `slide-up` / `curtain`)
- `bpt_overlay_colour` (string, hex colour)
- `bpt_skip_homepage` (boolean)

All are deleted when the plugin is uninstalled.

## Updates

Ships with [YahnisElsts/plugin-update-checker](https://github.com/YahnisElsts/plugin-update-checker) (installed via Composer, `vendor/` committed) pointed at `github.com/gakdesign/bonsai-page-transitions`. Sites with the plugin installed will see updates in **Plugins** in wp-admin, same as `bonsai-code-injector`.

To ship a new version:

1. Bump the `Version:` header in `bonsai-page-transitions.php` and add a `CHANGELOG.md` entry.
2. Commit and push to `main`.
3. Publish a GitHub Release tagged with the new version (release-assets mode is enabled, so attach a zip of the plugin folder — plain source-archive tags won't be picked up).

Sites check for updates every 6 hours (`$checkPeriod` argument to `buildUpdateChecker()`), or immediately if an admin clicks "Check again" on the Plugins screen.
