<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Home';
require __DIR__ . '/includes/header.php';
$user = current_user();
?>
<section class="hero">
    <p class="eyebrow">v0.1 / secure baseline</p>
    <h1>Application overview</h1>
    <p>Local PHP application for inspecting HTTP requests, session behavior, authorization checks, SQLite queries, and HTML or JSON responses.</p>
    <p class="status">
        <?php if ($user !== null): ?>
            Signed in as <strong><?= e($user['username']) ?></strong>.
        <?php else: ?>
            You are not signed in. <a href="/register.php">Create an account</a> to try authenticated features.
        <?php endif; ?>
    </p>
</section>
<section>
    <h2>Modules</h2>
    <div class="card-grid">
        <article class="card"><h3>User search</h3><p>GET query parameters and prepared username lookup.</p><a href="/search.php">Open</a></article>
        <article class="card"><h3>Authentication</h3><p>POST forms, password verification, cookies, and server-side sessions.</p><a href="<?= $user === null ? '/login.php' : '/profile.php' ?>">Open</a></article>
        <article class="card"><h3>Private notes</h3><p>Authenticated CRUD operations with server-side ownership checks.</p><a href="/notes.php">Open</a></article>
    </div>
</section>
<section class="api-demo">
    <h2>User API</h2>
    <p>Request <code>GET /api/users.php?id=&lt;id&gt;</code> with the browser Fetch API.</p>
    <form id="api-demo-form" class="compact-form">
        <label for="api-user-id">User ID</label>
        <input id="api-user-id" name="id" type="number" min="1" required>
        <button type="submit">Send request</button>
    </form>
    <pre id="api-result" tabindex="0">No request sent yet.</pre>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
