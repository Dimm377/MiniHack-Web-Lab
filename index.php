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
</section>
<div class="workbench-layout">
<section class="training-index">
    <div class="section-heading">
        <h2>Challenges</h2>
    </div>
    <div class="challenge-list">
        <?php $num = 1; foreach (challenge_definitions() as $slug => $definition): ?>
            <a class="challenge-row" href="/challenge.php?slug=<?= e($slug) ?>">
                <div class="challenge-number"><?= sprintf('%02d', $num++) ?></div>
                <div class="challenge-main">
                    <h3><?= e($definition['title']) ?></h3>
                    <p><?= e($definition['summary']) ?></p>
                </div>
                <div class="challenge-meta">
                    <span class="method"><?= e($definition['method']) ?></span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="api-demo">
    <div class="section-heading">
        <h2>HTTP Inspector</h2>
    </div>
    <div class="inspector-console">
        <form id="api-demo-form" novalidate>
            <div class="request-line">
                <span class="method">GET</span> 
                <code>/api/users.php?id=<input id="api-user-id" class="inline-input technical" name="id" type="number" min="1" placeholder="1" required></code>
                <button type="submit">Send</button>
            </div>
        </form>
        <div class="response-panel">
            <dl id="api-metadata" class="response-metadata">
                <dt>Status</dt><dd id="api-status" class="technical">—</dd>
                <dt>Content-Type</dt><dd class="technical">—</dd>
                <dt>Cache-Control</dt><dd class="technical">—</dd>
            </dl>
            <pre id="api-result" class="technical" tabindex="0" aria-label="JSON response"></pre>
        </div>
    </div>
    <noscript><p class="hint">The inspector needs JavaScript. You can <a href="/api/users.php?id=1">open the endpoint directly</a>.</p></noscript>
</section>
</div>

<section class="baseline-links">
    <h2 class="tools-heading">Utilities</h2>
    <ul class="inline-links">
        <li><a href="/search.php">User search</a></li>
        <li><a href="/notes.php">Private notes</a></li>
        <li><a href="<?= $user === null ? '/login.php' : '/profile.php' ?>"><?= $user === null ? 'Log in' : 'Your profile' ?></a></li>
    </ul>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
