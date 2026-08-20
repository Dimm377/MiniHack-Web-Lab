# Validation Report

Date: 2026-08-20  
Environment: Arch Linux, PHP 8.5.9, PDO SQLite, SQLite 3.53.4

## PASS

- Every PHP file passed `php -l`.
- `scripts/init_db.php` completed successfully twice. Tables, unique username enforcement, the note foreign key, ownership index, foreign-key activation through the application connection, database permissions, and Git ignore behavior were verified.
- Two test users were registered. Duplicate registration was rejected without exposing database details, password hashes were stored, valid logins succeeded, invalid logins returned the same generic message, and the session ID changed after login.
- Missing and invalid CSRF tokens returned 403 for login, note creation, and logout. Valid tokens succeeded. Failed CSRF checks did not mutate notes or destroy the authenticated session.
- Each user saw only their own note. User A could not delete User B's note, and adding another user ID to the profile or notes URL did not change the server-side ownership boundary.
- Search handled valid, empty, nonexistent, quote, wildcard, backslash, and HTML-like input without SQL or rendering errors.
- `<script>alert(1)</script>` and an HTML-like note title rendered as encoded text, with no raw script element in the response.
- The user API returned 200, 400, 404, and 405 as specified, valid JSON, an appropriate JSON content type, and only the `id` and `username` fields.
- Actual responses contained CSP, `X-Content-Type-Options: nosniff`, and `Referrer-Policy: same-origin`. The local HTTP session cookie contained `HttpOnly` and `SameSite=Lax` and correctly omitted `Secure`.
- The documented PHP development-server command returned 404 for `database`, `config`, `includes`, `scripts`, hidden files, `.git`, and traversal variants. Public pages and assets continued to return 200 after the protection fix.
- `git diff --check`, ignore-rule checks, tracked-file inspection, and secret/debug scans passed. No database, environment file, log, dump, credential, or private data is tracked.

## FAIL

- None.

## NOT TESTED

- Apache was not started. Its `.htaccess` rules were reviewed, but behavior still depends on Apache 2.4, `mod_rewrite`, and an `AllowOverride` configuration that permits the directives.

## Fix made during validation

The development router originally allowed `/.git/config`. `router.php` now rejects hidden path segments, and the root `.htaccess` provides matching Apache rules for hidden paths and private application directories. The relevant public and private-path regression checks passed after the change.
