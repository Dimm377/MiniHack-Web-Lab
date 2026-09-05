<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$query = trim(request_string($_GET, 'q'));
$results = [];
$message = null;
if (array_key_exists('q', $_GET)) {
    if ($query === '') {
        $message = 'Enter a username to search.';
    } elseif (strlen($query) > 30) {
        $message = 'Search terms must be 30 characters or fewer.';
    } else {
        // Escape LIKE metacharacters so the search text is interpreted literally.
        $escapedQuery = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query);
        $statement = db()->prepare("SELECT id, username FROM users WHERE username LIKE :query ESCAPE '\\' ORDER BY username LIMIT 20");
        $statement->execute(['query' => '%' . $escapedQuery . '%']);
        $results = $statement->fetchAll();
        if ($results === []) {
            $message = 'No matching users found.';
        }
    }
}

$pageTitle = 'User search';
require __DIR__ . '/includes/header.php';
?>
<section class="search-utility">
    <h1>User search</h1>
    <p class="hint">Search public usernames. Private notes and challenge progress are not included.</p>
    <form id="search-form" class="compact-form" method="get" action="/search.php" novalidate>
        <label for="q">Username</label>
        <div class="search-input"><input id="q" name="q" type="search" maxlength="30" value="<?= e($query) ?>" placeholder="Username or part of one" aria-describedby="search-help"><button id="clear-search" type="button" class="secondary" aria-label="Clear search" hidden>Clear</button></div>
        <button type="submit">Search</button>
    </form>
    <p id="search-help" class="hint">Literal match · up to 20 results · usernames and IDs only</p>
    <div id="search-results">
    <?php if ($message !== null): ?><p class="empty-state" role="status"><?= e($message) ?></p><?php endif; ?>
    <?php if ($results !== []): ?>
        <div class="section-heading"><h2>Matches</h2><span class="hint"><?= count($results) ?><?= count($results) === 20 ? ' (limit reached; narrow your search)' : '' ?></span></div>
        <ul class="result-list">
            <?php foreach ($results as $result): ?>
                <li><span><?= e($result['username']) ?></span><small>User ID <?= e($result['id']) ?></small></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
