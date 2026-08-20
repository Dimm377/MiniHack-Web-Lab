<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

if (current_user() !== null) {
    redirect('/profile.php');
}

$errors = [];
$username = '';
if (is_post()) {
    verify_csrf_or_abort();
    $username = trim(request_string($_POST, 'username'));
    $password = request_string($_POST, 'password');
    $confirmation = request_string($_POST, 'password_confirmation');

    if ($username === '') {
        $errors[] = 'Username is required.';
    } elseif (strlen($username) < 3 || strlen($username) > 30 || preg_match('/\A[A-Za-z0-9_]+\z/', $username) !== 1) {
        $errors[] = 'Username must be 3–30 characters using only letters, numbers, and underscores.';
    }
    if ($password === '') {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 8 || strlen($password) > 128) {
        $errors[] = 'Password must be 8–128 characters.';
    }
    if ($confirmation === '') {
        $errors[] = 'Password confirmation is required.';
    } elseif (!hash_equals($password, $confirmation)) {
        $errors[] = 'Password confirmation does not match.';
    }

    if ($errors === []) {
        $check = db()->prepare('SELECT 1 FROM users WHERE username = :username LIMIT 1');
        $check->execute(['username' => $username]);
        if ($check->fetchColumn() !== false) {
            $errors[] = 'That username is unavailable.';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            if (!is_string($passwordHash)) {
                throw new RuntimeException('Password hashing failed.');
            }
            try {
                $statement = db()->prepare('INSERT INTO users (username, password_hash) VALUES (:username, :password_hash)');
                $statement->execute(['username' => $username, 'password_hash' => $passwordHash]);
                set_flash('success', 'Registration complete. You can now log in.');
                redirect('/login.php');
            } catch (PDOException $exception) {
                if ($exception->getCode() === '23000') {
                    $errors[] = 'That username is unavailable.';
                } else {
                    throw $exception;
                }
            }
        }
    }
}

$pageTitle = 'Register';
require __DIR__ . '/includes/header.php';
?>
<section class="form-panel">
    <h1>Create an account</h1>
    <p>Usernames are public. Passwords are hashed before storage.</p>
    <?php if ($errors !== []): ?>
        <div class="alert alert-error" role="alert"><ul>
            <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
        </ul></div>
    <?php endif; ?>
    <form method="post" action="/register.php">
        <?= csrf_input() ?>
        <label for="username">Username</label>
        <input id="username" name="username" type="text" minlength="3" maxlength="30" pattern="[A-Za-z0-9_]+" autocomplete="username" value="<?= e($username) ?>" required autofocus>
        <label for="password">Password</label>
        <div class="password-row">
            <input id="password" name="password" type="password" minlength="8" maxlength="128" autocomplete="new-password" required>
            <button type="button" class="secondary toggle-password" data-target="password">Show</button>
        </div>
        <label for="password_confirmation">Confirm password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" maxlength="128" autocomplete="new-password" required>
        <button type="submit">Register</button>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
