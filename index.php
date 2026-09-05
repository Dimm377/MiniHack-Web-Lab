<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/challenges.php';

$pageTitle = 'Workbench';
require __DIR__ . '/includes/header.php';
$user = current_user();
?>
<section class="page-heading">
    <h1>Workbench</h1>
    <p>Inspect requests, headers, and response bodies.</p>
</section>
<div class="workbench-layout">
<section class="training-index">
    <h2>HTTP exercises</h2>
    <p>Recover a flag from a request parameter, response header or page source.</p>
    <div class="exercise-index">
        <?php foreach (challenge_definitions() as $slug => $definition): ?>
            <a href="/challenge.php?slug=<?= e($slug) ?>"><span><?= e($definition['title']) ?></span><code><?= e($definition['method']) ?></code></a>
        <?php endforeach; ?>
    </div>
    <p class="hint"><?= $user === null ? 'Log in to save progress.' : 'Progress saved to account.' ?></p>
    <h2 class="tools-heading">Baseline</h2>
    <dl class="tool-index">
        <dt><a href="/search.php">User search</a></dt><dd>Public usernames and IDs.</dd>
        <dt><a href="/notes.php">Private notes</a></dt><dd>Your findings.</dd>
        <dt><a href="<?= $user === null ? '/login.php' : '/profile.php' ?>"><?= $user === null ? 'Log in' : 'Your profile' ?></a></dt><dd>Session management.</dd>
    </dl>
</section>
<section class="api-demo">
    <div class="section-heading"><h2>Request inspector</h2><span class="hint">/api/users.php</span></div>
    <p>Look up a user ID.</p>
    <p class="request-line"><span class="method">GET</span> <code id="api-endpoint">/api/users.php?id=&lt;id&gt;</code></p>
    <form id="api-demo-form" class="compact-form" novalidate>
        <label for="api-user-id">User ID</label>
        <input id="api-user-id" name="id" type="number" min="1" placeholder="1" aria-describedby="api-help" required>
        <button type="submit">Send request</button>
    </form>
    <p id="api-help" class="hint">Try a registered user ID, or an unknown ID to inspect a 404.</p>
    <div class="response-heading"><h3>Response</h3><span id="api-status" role="status">Idle</span></div>
    <dl id="api-metadata" class="response-metadata"><dt>Content-Type</dt><dd>—</dd><dt>Cache-Control</dt><dd>—</dd></dl>
    <pre id="api-result" tabindex="0" aria-label="JSON response">Send a request to inspect the JSON body.</pre>
    <noscript><p class="hint">The inspector needs JavaScript. You can <a href="/api/users.php?id=1">open the endpoint directly</a>.</p></noscript>
</section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
