# MiniHack Web Lab

## Overview

MiniHack Web Lab is a deliberately small local PHP application for learning how browsers, HTTP, server-side logic, sessions, databases, HTML, and JSON fit together. Version 0.1 is a secure baseline, not a vulnerable target.

## Why This Project Exists

Security flaws make more sense after the underlying application flow is understood. This project starts with ordinary, defensible implementations of registration, authentication, authorization, database access, rendering, and a small API. Later training labs can compare isolated vulnerable examples with this baseline.

## Architecture

```text
Browser
   ↓
HTTP
   ↓
Apache / PHP
   ↓
Application
   ↓
SQLite
   ↓
Response (HTML / JSON)
   ↓
Browser
```

More detail is available in [docs/architecture.md](docs/architecture.md).

## Tech Stack

- PHP 8+ with PDO SQLite
- SQLite
- HTML5 and CSS3
- Vanilla JavaScript
- Apache, or PHP's built-in development server for local learning

There are no framework or Composer dependencies.

## Features

- Account registration with server-side validation and password hashing
- Login, cookie-backed PHP sessions, and POST-only logout
- Authenticated profile derived from the session
- Public username search using a query parameter
- Owner-only note creation, listing, and deletion
- JSON user lookup API with an educational `fetch()` example
- CSRF protection, prepared statements, output encoding, and generic errors

## Setup

Prerequisites:

- PHP 8.0 or newer
- PDO and PDO SQLite PHP extensions
- SQLite 3 (helpful for inspection, but not required by the application)

Check PHP support:

```bash
php -v
php -m | grep -Ei 'PDO|sqlite'
```

Initialize the database from the project root:

```bash
php scripts/init_db.php
```

The command is idempotent: it creates missing tables and indexes without replacing existing data. The runtime database is ignored by Git.

For PHP's local development server, use the included router so private application directories cannot be served as static files:

```bash
php -S 127.0.0.1:8080 router.php
```

Then open `http://127.0.0.1:8080`. HTTP is appropriate only for local learning. The session cookie is `HttpOnly` and `SameSite=Lax`; its `Secure` flag is enabled automatically only when the request actually uses HTTPS.

For Apache, point the site/document root at this project, ensure PHP, PDO SQLite, and `mod_rewrite` are enabled, and allow the project `.htaccess` rules. A stronger deployment layout would place only public entry points under the document root; this version keeps the flat structure visible for learning and is intended for local use only.

## Learning Objectives

The application demonstrates HTTP requests and responses, GET and POST, forms, query parameters, cookies, PHP sessions, authentication, authorization, SQL, prepared statements, HTML rendering, output encoding, JSON APIs, and browser `fetch()`.

## Security Model

> **v0.1 is the secure baseline.**

Passwords are hashed, SQL parameters are bound, untrusted output is escaped, state-changing forms use CSRF tokens, and private note access is constrained by the authenticated user ID on the server. Future intentionally vulnerable labs must remain isolated, clearly labeled, local-only, and documented; they must not silently weaken the baseline.

See [SECURITY.md](SECURITY.md) for the project policy.

## Validation

The v0.1 runtime validation covered PHP syntax, database initialization, authentication, CSRF, authorization, output encoding, API behavior, security headers, and internal-file protection. See [docs/validation.md](docs/validation.md) for the results and the remaining Apache-specific check.


## License

MIT. See [LICENSE](LICENSE).
