<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/challenges.php';
$path = database_path();
$directory = dirname($path);
if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
    fwrite(STDERR, "Could not create the database directory.\n");
    exit(1);
}

try {
    $pdo = open_database($path, true);
    $pdo->beginTransaction();
    $pdo->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS notes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            content TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );

        CREATE INDEX IF NOT EXISTS idx_notes_user_created
            ON notes(user_id, created_at DESC);

        CREATE TABLE IF NOT EXISTS solves (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            challenge_slug TEXT NOT NULL,
            solved_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE (user_id, challenge_slug)
        );
        SQL);
    $pdo->commit();
    chmod($path, 0600);

    $secretPath = instance_secret_path();
    if (!is_file($secretPath)) {
        $bytesWritten = file_put_contents($secretPath, bin2hex(random_bytes(32)) . "\n", LOCK_EX);
        if ($bytesWritten === false) {
            throw new RuntimeException('Could not create the challenge instance secret.');
        }
    }
    if (!chmod($secretPath, 0600)) {
        throw new RuntimeException('Could not restrict the challenge instance secret permissions.');
    }
    load_instance_secret();

    fwrite(STDOUT, "Database initialized at {$path}\n");
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Database initialization failed: {$exception->getMessage()}\n");
    exit(1);
}
