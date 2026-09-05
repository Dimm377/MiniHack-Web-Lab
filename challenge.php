<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/challenges.php';
require_once __DIR__ . '/config/database.php';

$user = require_auth();
$slug = request_string($_GET, 'slug');
$challenge = challenge_definition($slug);
header('Cache-Control: no-store');

if ($challenge === null) {
    http_response_code(404);
    $pageTitle = 'Challenge not found';
    require __DIR__ . '/includes/header.php';
    echo '<section><h1>Challenge not found</h1><p>The requested challenge does not exist.</p></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$expectedFlag = challenge_flag($user['id'], $slug);

if ($slug === 'response-headers' && !is_post()) {
    header('X-MiniHack-Flag: ' . $expectedFlag);
}

$error = null;
if (is_post()) {
    verify_csrf_or_abort();
    $submittedFlag = trim(request_string($_POST, 'flag'));

    if ($submittedFlag === '' || strlen($submittedFlag) > 80 || !hash_equals($expectedFlag, $submittedFlag)) {
        $error = 'The submitted flag is not correct.';
    } else {
        $statement = db()->prepare(
            'INSERT INTO solves (user_id, challenge_slug) VALUES (:user_id, :challenge_slug) '
            . 'ON CONFLICT(user_id, challenge_slug) DO NOTHING'
        );
        $statement->execute(['user_id' => $user['id'], 'challenge_slug' => $slug]);

        set_flash(
            'success',
            $statement->rowCount() === 1 ? 'Challenge solved.' : 'Challenge was already solved.'
        );
        redirect('/challenge.php?slug=' . rawurlencode($slug));
    }
}

$statement = db()->prepare(
    'SELECT solved_at FROM solves WHERE user_id = :user_id AND challenge_slug = :challenge_slug'
);
$statement->execute(['user_id' => $user['id'], 'challenge_slug' => $slug]);
$solvedAt = $statement->fetchColumn();
$queryUnlocked = $slug === 'query-parameters' && request_string($_GET, 'inspect') === 'request';

$pageTitle = $challenge['title'];
require __DIR__ . '/includes/header.php';
?>
<?php if ($slug === 'page-source'): ?>
<!-- MiniHack challenge flag: <?= e($expectedFlag) ?> -->
<?php endif; ?>
<section class="page-heading">
    <p><a href="/challenges.php">&larr; All challenges</a></p>
    <h1><?= e($challenge['title']) ?></h1>
    <p><?= e($challenge['summary']) ?></p>
    <p class="challenge-status <?= $solvedAt !== false ? 'is-solved' : '' ?>">
        <?= $solvedAt !== false ? 'solved ' . e($solvedAt) . ' UTC' : 'unsolved' ?>
    </p>
</section>
<p class="request-line"><span class="method"><?= e($challenge['method']) ?></span> <code>/challenge.php?slug=<?= e($slug) ?><?= $queryUnlocked ? '&amp;inspect=request' : '' ?></code></p>
<div class="exercise-layout">
<section class="challenge-task">
    <h2>Investigation</h2>
    <ol>
        <?php foreach ($challenge['instructions'] as $instruction): ?>
            <li><?= e($instruction) ?></li>
        <?php endforeach; ?>
    </ol>

    <?php if ($slug === 'query-parameters'): ?>
        <p class="hint">Required parameter: <code>inspect=request</code></p>
        <?php if ($queryUnlocked): ?>
            <div class="challenge-output">
                <span>Server response</span>
                <code><?= e($expectedFlag) ?></code>
            </div>
        <?php endif; ?>
    <?php elseif ($slug === 'response-headers'): ?>
        <p class="hint">Inspect the current document response, not a CSS or JavaScript request.</p>
    <?php elseif ($slug === 'page-source'): ?>
        <p class="hint">The rendered page intentionally does not display the flag.</p>
    <?php endif; ?>
</section>

<section class="flag-submit">
    <h2>Submit flag</h2>
    <?php if ($error !== null): ?>
        <p id="flag-error" class="alert alert-error" role="alert" tabindex="-1" data-error-summary><?= e($error) ?></p>
    <?php endif; ?>
    <p class="hint">Paste the complete flag, including <code>MHL{}</code>.</p>
    <form method="post" action="/challenge.php?slug=<?= e(rawurlencode($slug)) ?>" novalidate>
        <?= csrf_input() ?>
        <label for="flag">Flag</label>
        <input id="flag" class="technical" name="flag" type="text" maxlength="80" autocomplete="off" spellcheck="false" placeholder="MHL{...}"<?= $error !== null ? ' aria-invalid="true" aria-describedby="flag-error"' : '' ?> required>
        <button type="submit">Submit flag</button>
    </form>
</section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
