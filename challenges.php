<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/challenges.php';
require_once __DIR__ . '/config/database.php';

$user = require_auth();
$definitions = challenge_definitions();
header('Cache-Control: no-store');

$statement = db()->prepare('SELECT challenge_slug, solved_at FROM solves WHERE user_id = :user_id');
$statement->execute(['user_id' => $user['id']]);
$solvedRows = $statement->fetchAll();
$solves = [];
foreach ($solvedRows as $row) {
    if (is_string($row['challenge_slug']) && isset($definitions[$row['challenge_slug']])) {
        $solves[$row['challenge_slug']] = (string) $row['solved_at'];
    }
}

$pageTitle = 'Challenges';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading catalog-heading">
    <div>
    <h1>Challenges</h1>
    <p>Inspect the exchange. Recover your flag. Record the solve.</p>
    </div>
    <div class="progress"><span class="technical"><?= count($solves) ?> / <?= count($definitions) ?></span> solved<progress value="<?= count($solves) ?>" max="<?= count($definitions) ?>" aria-label="Challenges solved"></progress></div>
</section>
<section aria-label="HTTP exercises">
    <div class="section-heading"><h2>HTTP fundamentals</h2><span class="hint">Browser developer tools · GET requests</span></div>
    <div class="challenge-list">
        <?php foreach ($definitions as $slug => $challenge): ?>
            <?php $isSolved = isset($solves[$slug]); ?>
            <article class="challenge-row">
                <div>
                    <p class="eyebrow technical"><?= e($challenge['method']) ?></p>
                    <h3><a href="/challenge.php?slug=<?= e(rawurlencode($slug)) ?>"><?= e($challenge['title']) ?></a></h3>
                </div>
                <p><?= e($challenge['summary']) ?></p>
                <span class="challenge-status <?= $isSolved ? 'is-solved' : '' ?>">
                    <?= $isSolved ? 'solved' : 'unsolved' ?>
                </span>
            </article>
        <?php endforeach; ?>
    </div>
    <p class="hint">Each flag belongs to your account. Repeating a solve keeps the same result.</p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
