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
    } elseif (strlen($password) < 8 || strlen($password) > 72 || str_contains($password, "\0")) {
        // PASSWORD_DEFAULT currently uses bcrypt, which accepts at most 72 bytes.
        $errors[] = 'Password must be 8–72 bytes and contain no null characters.';
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
    <?php if ($errors !== []): ?>
        <div id="register-errors" class="alert alert-error" role="alert" tabindex="-1" data-error-summary><ul>
            <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
        </ul></div>
    <?php endif; ?>
    <form method="post" action="/register.php" novalidate>
        <?= csrf_input() ?>
        <label for="username">Username</label>
        <input id="username" name="username" type="text" minlength="3" maxlength="30" pattern="[A-Za-z0-9_]+" autocomplete="username" value="<?= e($username) ?>" aria-describedby="username-help<?= $errors !== [] ? ' register-errors' : '' ?>"<?= $errors !== [] ? ' aria-invalid="true"' : '' ?> required autofocus>
        <small id="username-help" class="hint">3–30 letters, numbers or underscores.</small>
        <label for="password">Password</label>
        <div class="password-row">
            <input id="password" name="password" type="password" minlength="8" maxlength="72" autocomplete="new-password" aria-describedby="password-help<?= $errors !== [] ? ' register-errors' : '' ?>"<?= $errors !== [] ? ' aria-invalid="true"' : '' ?> required>
            <button type="button" class="secondary toggle-password" data-target="password" aria-label="Show password" aria-pressed="false" hidden>Show</button>
        </div>
        <small id="password-help" class="hint">8–72 bytes. Non-ASCII characters may use more than one byte.</small>
        <label for="password_confirmation">Confirm password</label>
        <div class="password-row">
            <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" maxlength="72" autocomplete="new-password"<?= $errors !== [] ? ' aria-invalid="true" aria-describedby="register-errors"' : '' ?> required>
            <button type="button" class="secondary toggle-password" data-target="password_confirmation" aria-label="Show confirm password" aria-pressed="false" hidden>Show</button>
        </div>
        <button type="submit">Register</button>
    </form>
    <p class="form-switch">Already registered? <a href="/login.php">Log in</a></p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
