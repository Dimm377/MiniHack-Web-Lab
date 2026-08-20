<?php

declare(strict_types=1);

/** @return array{id: int, username: string}|null */
function current_user(): ?array
{
    $user = $_SESSION['user'] ?? null;
    if (!is_array($user) || !isset($user['id'], $user['username'])) {
        return null;
    }
    $id = positive_int($user['id']);
    if ($id === null || !is_string($user['username'])) {
        return null;
    }
    return ['id' => $id, 'username' => $user['username']];
}

function require_auth(): array
{
    $user = current_user();
    if ($user === null) {
        set_flash('error', 'Please log in to continue.');
        redirect('/login.php');
    }
    return $user;
}

function log_in(int $id, string $username): void
{
    // A new session ID prevents an attacker from reusing a pre-login session ID.
    session_regenerate_id(true);
    unset($_SESSION['csrf_token']);
    $_SESSION['user'] = ['id' => $id, 'username' => $username];
}

function log_out(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => 'Lax',
        ]);
    }
    session_destroy();
}
