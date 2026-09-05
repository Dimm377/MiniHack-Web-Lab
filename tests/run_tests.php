<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/bootstrap.php';

$root = dirname(__DIR__);
$normalSnapshot = data_snapshot($root . '/database');
$testDirectory = sys_get_temp_dir() . '/minihack-test-' . bin2hex(random_bytes(16));
if (!mkdir($testDirectory, 0700)) {
    throw new RuntimeException('Could not create isolated test directory.');
}
$server = null;
$pdo = null;
$passed = 0;
$failed = 0;
$previousDirectory = getenv('MINIHACK_DATA_DIR');
$previousWorkers = getenv('PHP_CLI_SERVER_WORKERS');
putenv('MINIHACK_DATA_DIR=' . $testDirectory);
// Always own exactly one server process, even if the caller configured workers.
putenv('PHP_CLI_SERVER_WORKERS');
register_shutdown_function(static function () use (&$server, &$pdo, $testDirectory, $previousDirectory, $previousWorkers): void {
    stop_server($server);
    $server = null;
    $pdo = null;
    if (is_dir($testDirectory) && !is_link($testDirectory)) {
        remove_test_tree($testDirectory);
    }
    putenv($previousDirectory === false ? 'MINIHACK_DATA_DIR' : 'MINIHACK_DATA_DIR=' . $previousDirectory);
    putenv($previousWorkers === false ? 'PHP_CLI_SERVER_WORKERS' : 'PHP_CLI_SERVER_WORKERS=' . $previousWorkers);
});

try {
    require $root . '/config/database.php';
    require $root . '/includes/challenges.php';
    // Fail BEFORE initialization if either path ever regresses to developer data.
    if (database_path() !== $testDirectory . '/minihack.sqlite' || instance_secret_path() !== $testDirectory . '/instance_secret') {
        throw new RuntimeException('Unsafe runtime paths: refusing to initialize or run tests.');
    }
    if (!extension_loaded('pdo_sqlite')) {
        throw new RuntimeException('Enable PDO SQLite in the PHP configuration before running tests.');
    }
    mkdir($testDirectory . '/sessions', 0700);
    run_php([$root . '/scripts/init_db.php']);
    $secretBefore = hash_file('sha256', $testDirectory . '/instance_secret');
    run_php([$root . '/scripts/init_db.php']);
    check(hash_file('sha256', $testDirectory . '/instance_secret') === $secretBefore, 'Initialization preserves the instance secret');
    $pdo = open_database($testDirectory . '/minihack.sqlite');
    check((int) $pdo->query('PRAGMA foreign_keys')->fetchColumn() === 1, 'Foreign keys are enabled');
    check((int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0, 'Test database starts empty');
    $server = start_server($root, $testDirectory);
    require __DIR__ . '/regression.php';
} catch (Throwable $error) {
    $failed++;
    fwrite(STDERR, 'FAIL: ' . $error->getMessage() . "\n");
} finally {
    stop_server($server);
    $server = null;
    $pdo = null;
    check(data_snapshot($root . '/database') === $normalSnapshot, 'Developer runtime files are untouched');
    remove_test_tree($testDirectory);
    check(!file_exists($testDirectory), 'Only the owned test directory was cleaned up');
}

echo "$passed passed; $failed failed.\n";
exit($failed === 0 ? 0 : 1);
