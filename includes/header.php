<?php

declare(strict_types=1);

$pageTitle = isset($pageTitle) ? (string) $pageTitle : 'MiniHack Web Lab';
$navUser = current_user();
$flash = take_flash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | MiniHack Web Lab</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="/assets/js/app.js" defer></script>
</head>
<body>
<header class="site-header">
    <a class="brand" href="/">MiniHack Web Lab</a>
    <nav aria-label="Main navigation">
        <a href="/">Home</a>
        <a href="/search.php">Search</a>
        <?php if ($navUser !== null): ?>
            <a href="/challenges.php">Challenges</a>
            <a href="/notes.php">Notes</a>
            <a href="/profile.php">Profile</a>
            <form class="inline" method="post" action="/logout.php">
                <?= csrf_input() ?>
                <button class="link-button" type="submit">Log out</button>
            </form>
        <?php else: ?>
            <a href="/register.php">Register</a>
            <a href="/login.php">Log in</a>
        <?php endif; ?>
    </nav>
</header>
<main class="container">
    <?php if ($flash !== null): ?>
        <p class="alert alert-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></p>
    <?php endif; ?>
