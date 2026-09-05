# Validation Report

Date: 2026-09-05
Local environment: Arch Linux, PHP 8.5.10, PDO SQLite, Chromium headless

This document distinguishes checks executed in this repository state from checks that remain environment-specific. Re-run them after future changes.

## Automated checks

The following commands passed locally:

~~~bash
git ls-files -z '*.php' | xargs -0 -n1 php -l
php -l tests/bootstrap.php
php -l tests/regression.php
node --check assets/js/app.js
php tests/run_tests.php
git diff --check
python scripts/audit_project.py <project-root> --mode strict --no-write
~~~

PDO SQLite is installed but disabled in the workstation's default <code>php.ini</code>, so the local database/test commands were run with a temporary scan directory containing <code>extension=pdo_sqlite</code>. This changes no repository or system configuration.

The regression suite reported **225 passed; 0 failed**. It covered:

- isolated, idempotent database/secret initialization and <code>0600</code> permissions;
- developer runtime fingerprint unchanged before and after the complete suite;
- registration, duplicate usernames, password hashing, and invalid password boundaries;
- valid/invalid login, SQL injection attempts, generic errors, session and CSRF rotation, and invalidated pre-login sessions;
- missing, invalid, and valid CSRF for registration, login, note mutation, challenge submission, and logout, including non-mutation assertions;
- session-derived profile/note ownership, cross-user reads/deletes, and ignored client ownership IDs;
- reflected search/login encoding and stored note title/content encoding;
- literal quote, percent, underscore, and backslash search behavior;
- API 200/400/404/405/500, JSON content type, Allow, caching, and field filtering;
- challenge authentication, exact query behavior, document-response header, source comment, per-user/distinct flags, generic invalid submissions, idempotent solves, and isolated progress;
- CSP, nosniff, referrer policy, challenge/API caching, and HTTPS/local cookie settings;
- private repository paths, hidden files, tests, patch/debug files, path-info, traversal/encoding variants, and instance-secret non-disclosure;
- generic HTML/API database failures and generic missing-secret failure;
- POST-only, CSRF-protected logout, cookie expiry, and destroyed session state.

The frontend contract audit reported zero errors, warnings, unresolved items,
or violations.

## Manual browser checks

Chromium exercised registration, login, note creation, the notes list, challenge detail, and the API inspector. The following viewports were inspected:

- 1360 × 900: public workbench
- 1280 × 900: authenticated notes with a long title and long technical content
- 390 × 844: workbench, notes, and response-header challenge

The browser confirmed an actual 200 API response and public JSON body. It measured the mobile notes and challenge documents at or below the available content width with no horizontal overflow. Long flags/endpoints, usernames, notes, and response bodies wrapped or scrolled within their owners. Navigation, forms, feedback, delete disclosure, and footer remained reachable.

## CI configuration

GitHub Actions uses least-privilege <code>contents: read</code>, runs syntax checks from tracked PHP files, and executes the same regression suite on PHP 8.2 and 8.5 with PDO SQLite. The local run above does not claim that GitHub-hosted jobs have executed for these uncommitted changes.

Repository settings cannot be changed from this checkout. Protect <code>main</code> and require the workflow's PHP test checks before merge.

## Not tested

- Apache 2.4 was not started. The <code>.htaccess</code> allowlist was reviewed, but its behavior depends on <code>mod_rewrite</code>, Apache configuration, and <code>AllowOverride</code>.
- Windows, macOS, mobile Safari, and mobile Chrome were not run.
- Screen-reader output and physical touch/virtual-keyboard behavior were not tested.
- The GitHub Actions workflow has not run against these working-tree changes.
- The official DESIGN.md linter was attempted twice. Package retrieval failed
  first under restricted DNS and then timed out with network access, so its
  result is unavailable. The project-specific strict UI audit did pass.

No result outside the automated and manual checks above is claimed.
