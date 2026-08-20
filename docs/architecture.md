# Architecture

## Request lifecycle

1. The browser sends an HTTP request to a PHP entry point.
2. Shared helpers configure the session and response security headers.
3. The entry point validates the HTTP method and user-controlled input.
4. Protected pages derive identity from the server-side session and enforce authorization.
5. PDO executes parameterized SQL against SQLite.
6. The entry point renders encoded HTML or returns JSON with an explicit status code.

## Directory responsibilities

- Root PHP files are browser-facing HTML entry points.
- `api/` contains the JSON user endpoint.
- `assets/` contains static CSS and JavaScript.
- `config/` owns database connection configuration.
- `includes/` owns focused session, authentication, CSRF, rendering, and shared helpers.
- `database/` contains the ignored runtime SQLite file and an Apache access-denial rule.
- `scripts/` contains the CLI-only, idempotent database initializer.
- `docs/` contains project documentation.

## Authentication and session flow

Registration validates the submitted username and password, hashes the password with `password_hash()`, and inserts it through a prepared statement. Login retrieves the matching password hash and checks it with `password_verify()`. A successful login regenerates the session ID, rotates the CSRF token, and stores only the numeric user ID and username.

The browser receives a PHP session cookie. It is `HttpOnly`, `SameSite=Lax`, and `Secure` when HTTPS is actually used. The local HTTP development mode cannot provide transport encryption. Logout requires a CSRF-protected POST, clears session data, expires the cookie, and destroys the server-side session.

## Database access

`config/database.php` creates the PDO SQLite connection, enables exception mode and foreign-key enforcement, and disables emulated prepared statements. `scripts/init_db.php` owns schema creation. Application queries use prepared statements for user-controlled values.

The SQLite file is deliberately absent from version control. Apache `.htaccess` rules and the PHP development-server `router.php` block private directories and hidden paths such as `.git`.

## Authorization boundaries

`require_auth()` protects profile and notes. Profile lookup uses only the user ID in the authenticated session. Notes are selected with `WHERE user_id = :user_id`; deletion uses both the submitted note ID and the authenticated user ID. A hidden form field identifies a note but never establishes ownership.

## API request flow

`GET /api/users.php?id=<id>` rejects unsupported methods, validates a positive integer ID, selects only `id` and `username`, and returns JSON. It uses 200, 400, 404, 405, or 500 status codes without returning SQL errors, stack traces, password hashes, or private note data.
