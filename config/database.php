<?php

declare(strict_types=1);

function data_directory(): string
{
    $override = getenv('MINIHACK_DATA_DIR');
    if ($override === false) {
        return dirname(__DIR__) . '/database';
    }
    // A typo must fail closed, never silently fall back to developer data.
    if ($override === '' || trim($override, '/') === '' || str_contains($override, "\0") || $override[0] !== '/') {
        throw new RuntimeException('MINIHACK_DATA_DIR must be an absolute directory path.');
    }
    return rtrim($override, '/');
}

function database_path(): string
{
    return data_directory() . '/minihack.sqlite';
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
