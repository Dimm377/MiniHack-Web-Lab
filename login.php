<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

if (current_user() !== null) {
    redirect('/profile.php');
}

$error = null;
$username = '';
if (is_post()) {
    verify_csrf_or_abort();
    $username = trim(request_string($_POST, 'username'));
    $password = request_string($_POST, 'password');
    if ($username === '' || $password === '' || strlen($username) > 30 || strlen($password) > 128) {
        $error = 'Invalid username or password.';
    } else {
        $statement = db()->prepare('SELECT id, username, password_hash FROM users WHERE username = :username LIMIT 1');
        $statement->execute(['username' => $username]);
        $user = $statement->fetch();
        if (!is_array($user) || !password_verify($password, (string) $user['password_hash'])) {
            // The same message is used whether the username or password is wrong.
            $error = 'Invalid username or password.';
        } else {
            log_in((int) $user['id'], (string) $user['username']);
            redirect('/profile.php');
        }
    }
}

$pageTitle = 'Log in';
require __DIR__ . '/includes/header.php';
?>
<section class="form-panel">
    <h1>Log in</h1>
    <?php if ($error !== null): ?><p class="alert alert-error" role="alert"><?= e($error) ?></p><?php endif; ?>
    <form method="post" action="/login.php">
        <?= csrf_input() ?>
        <label for="username">Username</label>
        <input id="username" name="username" type="text" maxlength="30" autocomplete="username" value="<?= e($username) ?>" required autofocus>
        <label for="password">Password</label>
        <div class="password-row">
            <input id="password" name="password" type="password" maxlength="128" autocomplete="current-password" required>
            <button type="button" class="secondary toggle-password" data-target="password">Show</button>
        </div>
        <button type="submit">Log in</button>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
