# Architecture

MiniHack Web Lab v0.2 keeps one small PHP application boundary:

~~~text
Browser
  → allowlist router / Apache rules
  → public PHP entry point
  → session, CSRF, authorization, and input boundary
  → parameterized PDO query
  → encoded HTML or explicit JSON response
~~~

The secure application baseline and educational challenge mechanics share focused helpers but have separate responsibilities.

## Request and file boundaries

Root PHP entry points render pages; <code>api/users.php</code> returns JSON. <code>includes/</code> owns shared sessions, authentication, CSRF, rendering helpers, and the challenge registry. <code>config/</code> owns the SQLite path and connection. <code>scripts/init_db.php</code> is CLI-only. <code>assets/</code> contains native CSS and JavaScript. <code>docs/</code> contains maintained project documentation.

The PHP development <code>router.php</code> is a public-resource allowlist. It serves only known page/API entry points and the two application assets. Everything else returns a plain 404. The Apache <code>.htaccess</code> mirrors that public set and also disables indexes, MultiViews, and path-info routing.

## Data paths and initialization

<code>data_directory()</code> resolves to <code>database/</code> by default. An absolute, non-root <code>MINIHACK_DATA_DIR</code> overrides it for isolated processes. Both <code>database_path()</code> and <code>instance_secret_path()</code> derive from this one boundary, so a test cannot isolate one and accidentally reuse the other.

The initializer creates its selected directory with owner-only permissions, enables SQLite foreign keys, creates the schema idempotently, and applies <code>0600</code> to the database and secret. The secret uses exclusive file creation so concurrent initializers cannot overwrite an existing value.

The schema has three focused tables:

- <code>users</code>: unique username, password hash, and creation time
- <code>notes</code>: owner ID, title, content, and creation time
- <code>solves</code>: owner ID, challenge slug, and solve time, unique per user/slug

## Authentication and authorization

Registration validates public usernames and passwords at the server boundary, then hashes passwords. The 72-byte password cap matches the current bcrypt limit, and null bytes are rejected. Login performs an exact prepared lookup, uses a generic failure message, verifies the hash, regenerates the session ID, and rotates the CSRF token.

The session contains only the numeric user ID and username. Profile and note queries derive identity from this server-side session. Note listing filters by owner, and deletion requires both the note ID and authenticated owner ID. Client IDs may select a resource but never establish ownership.

Logout is POST-only, verifies CSRF, clears the session, expires its cookie, and destroys server-side state.

## Output and API

HTML entry points encode untrusted values with <code>htmlspecialchars()</code>. Stored note newlines are added only after encoding. Search escapes SQLite LIKE metacharacters and returns a maximum of 20 public results.

<code>GET /api/users.php?id=&lt;positive integer&gt;</code> returns only <code>id</code> and <code>username</code>. It returns JSON for 200, 400, 404, 405, and 500 outcomes, sets <code>Cache-Control: no-store</code>, and hides PDO errors, filesystem paths, and private fields.

Shared HTML responses send CSP, <code>X-Content-Type-Options: nosniff</code>, and <code>Referrer-Policy: same-origin</code>. Exceptions are logged server-side and rendered as a generic 500 page.

## Challenge registry and flags

<code>includes/challenges.php</code> is the code-defined challenge registry. A database catalog would add migration and query complexity without helping this small lab. Each definition contains a slug, title, summary, HTTP method, and instructions.

Released slugs are immutable. Renaming one requires an explicit migration of solve records and a decision about compatibility with flags derived from the old slug.

For a known slug and authenticated user, the application computes:

~~~text
MHL{ first 24 hex characters of HMAC-SHA256(user_id:slug, instance_secret) }
~~~

The query challenge releases the flag only for the exact documented query value. The header challenge adds <code>X-MiniHack-Flag</code> to the document response. The source challenge places the flag in an HTML comment. Valid submissions use constant-time comparison and an idempotent insert. All challenge responses use <code>Cache-Control: no-store</code>.

## Test lifecycle

<code>tests/run_tests.php</code> creates a cryptographically unique temporary directory, sets <code>MINIHACK_DATA_DIR</code>, disables inherited built-in-server workers, and uses a separate session directory. Before initialization it asserts that both runtime paths resolve inside that test directory.

The runner initializes twice to test idempotency, starts one PHP process on an OS-selected loopback port, and polls until both the endpoint and its session file exist. It stops that exact process, closes SQLite, recursively removes only the fresh test directory, and verifies the normal developer-data fingerprint is unchanged. <code>tests/regression.php</code> exercises the application through real HTTP requests and uses the isolated database only for setup-independent assertions and safe failure injection.
