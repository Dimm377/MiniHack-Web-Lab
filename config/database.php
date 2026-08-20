<?php

declare(strict_types=1);

function database_path(): string
{
    return dirname(__DIR__) . '/database/minihack.sqlite';
}

function open_database(string $path, bool $allowCreate = false): PDO
{
    if (!$allowCreate && !is_file($path)) {
        throw new RuntimeException('The database has not been initialized.');
    }
    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    // SQLite requires foreign-key enforcement to be enabled per connection.
    $pdo->exec('PRAGMA foreign_keys = ON');
    return $pdo;
}

function db(): PDO
{
    static $pdo = null;
    if (!$pdo instanceof PDO) {
        $pdo = open_database(database_path());
    }
    return $pdo;
}
