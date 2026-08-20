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
<section>
    <p class="eyebrow">HTTP exploration / phase A</p>
    <h1>Challenges</h1>
    <p>Inspect real requests and responses, recover your per-user flag, and submit it for server-side verification.</p>
    <p class="progress"><span class="technical"><?= count($solves) ?> / <?= count($definitions) ?></span> solved</p>

    <div class="challenge-list">
        <?php foreach ($definitions as $slug => $challenge): ?>
            <?php $isSolved = isset($solves[$slug]); ?>
            <article class="challenge-row">
                <div>
                    <p class="eyebrow"><?= e($challenge['method']) ?></p>
                    <h2><?= e($challenge['title']) ?></h2>
                </div>
                <p><?= e($challenge['summary']) ?></p>
                <span class="challenge-status <?= $isSolved ? 'is-solved' : '' ?>">
                    <?= $isSolved ? 'solved' : 'unsolved' ?>
                </span>
                <a href="/challenge.php?slug=<?= e(rawurlencode($slug)) ?>">Open</a>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
