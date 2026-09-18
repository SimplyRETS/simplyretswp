# Repository guidance for coding agents

## Project layout

This repository contains the SimplyRETS WordPress plugin. The installable plugin source is in `src/`; `src/simply-rets.php` is its entry point. Listing and form HTML is generated mainly in `src/simply-rets-renderer.php`, with additional markup in the shortcode, widget, open house, map, admin, and post page files. Frontend CSS and JavaScript are in `src/assets/`.

## Primary rule: preserve compatibility

Treat existing behavior and rendered HTML as public interfaces. Sites may depend on the plugin's markup through theme CSS, custom JavaScript, shortcodes, widgets, and WordPress hooks.

- Preserve existing HTML structure, element order, classes, IDs, form names and values, `data-*` attributes, and URL/query parameter behavior unless the task explicitly calls for a breaking change.
- Preserve shortcode names and attributes, widget settings, post type and rewrite behavior, option keys, hooks, and public PHP methods unless a change is explicitly requested.
- Keep frontend CSS changes scoped to the relevant plugin components. Check that new rules do not alter existing layouts or override theme customizations unexpectedly.
- When changing markup, search its selectors and attributes across PHP, CSS, JavaScript, and documentation. Update all in-repository consumers together while keeping existing external integrations working.
- Prefer additive changes. If a compatibility break is necessary, explain the affected interface and migration path before implementing it.
- An unavoidable breaking change requires a major version bump. The current `3.2.9` line would move to version `4`. Update the plugin header, `SIMPLYRETSWP_VERSION`, the `Stable tag` in `src/readme.txt`, and release notes consistently when making that release.
- Do not reformat or restructure unrelated rendering code as part of a functional change; small diffs make compatibility review possible.

## Supported environments

- Support PHP 7.0.27 and later for now. Avoid syntax or runtime features that require a newer PHP version unless a major release explicitly raises the minimum.
- Read the `Requires at least` tag in `src/readme.txt` before changing WordPress compatibility behavior. That tag is the source of truth for the minimum supported WordPress version (currently `3.0.1`). Guard newer WordPress APIs and provide a compatible path where needed.
- Support standard WordPress themes and block themes. Preserve the existing block theme handling in `src/simply-rets-post-pages.php`.

## Working practices

- Follow the surrounding PHP and WordPress conventions in the file being edited. Use WordPress escaping and sanitization appropriate to the output or input context.
- Keep generated and third-party assets separate from plugin source. Do not edit minified vendor libraries or release ZIP files unless the task specifically concerns them.
- For a visual change, check the affected listing, details, search, widget, or map view in a WordPress environment when available. Compare the rendered DOM and appearance before and after, including a narrow viewport where relevant. OceanWP free is a useful regular test theme; also check a standard classic theme and a block theme when the change could affect theme integration. No particular customer theme or customization is designated as a baseline yet.
- For PHP changes, run `php -l` on changed PHP files. The test configuration is `src/phpunit.xml`; its tests require the WordPress test library (`WP_TESTS_DIR`, defaulting to `/tmp/wordpress-tests-lib`). Run the relevant tests when that environment is available, and report when it is not.
- Report any compatibility risk, unverified visual behavior, or required follow-up in the final summary.

## Local development

`Makefile` provides `make deps`, `make dev`, `make stop`, and `make logs`. `make dev` starts the Docker Compose WordPress environment. The repository's current automated test coverage is limited, so a passing test run alone does not establish visual compatibility.
