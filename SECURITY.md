# Security Policy

MiniHack Web Lab v0.2 is a local educational lab with two explicit boundaries:

- The application baseline is expected to remain secure.
- HTTP challenges expose only their documented, per-user training flags.

A challenge must not silently weaken registration, authentication, sessions, authorization, CSRF, output encoding, database access, error handling, private paths, or the public API. Challenge flags are learning artifacts, not authentication secrets.

## Baseline controls

The baseline uses <code>password_hash()</code> and <code>password_verify()</code>, PDO prepared statements, contextual HTML encoding, CSRF tokens, session ID regeneration after login, POST-only logout, server-derived ownership checks, generic authentication and server errors, and security response headers.

Session cookies are <code>HttpOnly</code>, <code>SameSite=Lax</code>, and <code>Secure</code> under HTTPS. The PHP development router and Apache rules expose an explicit set of public PHP entry points and static assets. Repository documents, hidden files, tests, configuration, scripts, the SQLite database, and <code>instance_secret</code> are not public HTTP resources.

Normal runtime data lives under <code>database/</code>. Tests set <code>MINIHACK_DATA_DIR</code> to a unique directory under the system temporary directory. The test runner checks the resolved database and secret paths before initialization, fingerprints the developer data directory, and removes only its owned test tree.

## Challenge controls

Flags use HMAC-SHA256 over the authenticated user ID and immutable challenge slug with a random 32-byte local instance secret. The database stores solve records, not expected flags. The unique <code>(user_id, challenge_slug)</code> constraint makes repeat submissions idempotent.

Released challenge slugs are immutable. A rename changes flag derivation and orphaned solve semantics, so it requires an explicit data and compatibility migration.

Challenge pages require authentication, submit flags through CSRF-protected POST requests, and return <code>Cache-Control: no-store</code>. Invalid and cross-user flags produce the same generic error. The instance secret and normal runtime files are ignored by Git and restricted to the local owner.

## Reporting

Report suspected baseline vulnerabilities privately to the repository owner. Include reproduction steps, impact, and affected version. Do not include real credentials or personal data. Accidental baseline vulnerabilities are defects; they must not be reclassified as challenge behavior.

This project is designed for local learning. Do not expose the built-in PHP server directly to an untrusted network.
