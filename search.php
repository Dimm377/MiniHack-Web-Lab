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
<section>
    <p class="eyebrow">GET + prepared statement</p>
    <h1>Search public users</h1>
    <form class="compact-form" method="get" action="/search.php">
        <label for="q">Username</label>
        <input id="q" name="q" type="search" maxlength="30" value="<?= e($query) ?>" placeholder="alice">
        <button type="submit">Search</button>
    </form>
    <?php if ($message !== null): ?><p class="alert" role="status"><?= e($message) ?></p><?php endif; ?>
    <?php if ($results !== []): ?>
        <ul class="result-list">
            <?php foreach ($results as $result): ?>
                <li><span><?= e($result['username']) ?></span><small>User ID <?= e($result['id']) ?></small></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
