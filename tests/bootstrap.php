<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

/** Fingerprint files without opening the developer database through SQLite. */
function data_snapshot(string $directory): array
{
    $snapshot = [];
    if (is_dir($directory)) {
        foreach (new DirectoryIterator($directory) as $file) {
            if ($file->isFile() && !$file->isLink()) {
                $snapshot[$file->getFilename()] = [hash_file('sha256', $file->getPathname()), $file->getMTime(), $file->getPerms()];
            }
        }
    }
    ksort($snapshot);
    return $snapshot;
}

/** Only called on the freshly-created test tree; never follow directory symlinks. */
function remove_test_tree(string $directory): void
{
    foreach (new DirectoryIterator($directory) as $file) {
        if ($file->isDot()) {
            continue;
        }
        if ($file->isDir() && !$file->isLink()) {
            remove_test_tree($file->getPathname());
        } else {
            unlink($file->getPathname());
        }
    }
    rmdir($directory);
}

function stop_server($server): void
{
    if (is_resource($server)) {
        if (proc_get_status($server)['running']) {
            proc_terminate($server);
            $deadline = microtime(true) + 3;
            while (proc_get_status($server)['running'] && microtime(true) < $deadline) {
                usleep(20000);
            }
            if (proc_get_status($server)['running']) {
                proc_terminate($server, 9);
            }
        }
        proc_close($server);
    }
}

function run_php(array $arguments): string
{
    $process = proc_open([PHP_BINARY, ...$arguments], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) {
        throw new RuntimeException('Could not start PHP.');
    }
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0) {
        throw new RuntimeException("PHP failed: $output $errors");
    }
    return $output;
}

function request(string $method, string $path, array $data = [], string $cookie = ''): array
{
    global $url;
    $context = stream_context_create(['http' => [
        'method' => $method,
        'header' => "Content-Type: application/x-www-form-urlencoded\r\nConnection: close\r\n" . ($cookie !== '' ? "Cookie: $cookie\r\n" : ''),
        'content' => $method === 'GET' ? '' : http_build_query($data),
        'ignore_errors' => true,
        'follow_location' => 0,
        'timeout' => 3,
    ]]);
    $body = @file_get_contents($url . $path, false, $context);
    $lines = $http_response_header ?? [];
    preg_match('/^HTTP\/\S+ (\d+)/', $lines[0] ?? '', $match);
    $headers = [];
    foreach (array_slice($lines, 1) as $line) {
        if (str_contains($line, ':')) {
            [$key, $value] = explode(':', $line, 2);
            $headers[strtolower($key)] = trim($value);
        }
    }
    return ['status' => (int) ($match[1] ?? 0), 'body' => (string) $body, 'headers' => $headers];
}

function get(string $path, string $cookie = ''): array
{
    return request('GET', $path, [], $cookie);
}

function post(string $path, array $data, string $cookie = ''): array
{
    return request('POST', $path, $data, $cookie);
}

function csrf(array $response): string
{
    if (!preg_match('/name="csrf_token"\s+value="([^"]+)"/', $response['body'], $match)) {
        throw new RuntimeException('Expected a CSRF token.');
    }
    return $match[1];
}

function cookie(array $response): string
{
    return explode(';', $response['headers']['set-cookie'] ?? '')[0];
}

function check(bool $condition, string $message): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "PASS: $message\n";
    } else {
        $failed++;
        echo "FAIL: $message\n";
    }
}

function start_server(string $root, string $testDirectory)
{
    global $url;
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
    if ($socket === false) {
        throw new RuntimeException("Cannot allocate test port: $errorMessage");
    }
    $address = stream_socket_get_name($socket, false);
    fclose($socket);
    $url = 'http://' . $address;
    $process = proc_open([
        PHP_BINARY, '-d', 'session.save_path=' . $testDirectory . '/sessions',
        '-d', 'error_log=' . $testDirectory . '/errors.log',
        '-S', $address, '-t', $root, $root . '/router.php',
    ], [0 => ['pipe', 'r'], 1 => ['file', $testDirectory . '/server.log', 'a'], 2 => ['file', $testDirectory . '/server.log', 'a']], $pipes, $root);
    if (!is_resource($process)) {
        throw new RuntimeException('Could not start test server.');
    }
    fclose($pipes[0]);
    try {
        $deadline = microtime(true) + 5;
        do {
            if (!proc_get_status($process)['running']) {
                throw new RuntimeException('Test server exited before becoming ready.');
            }
            $response = get('/login.php');
            $sessionId = substr(cookie($response), strlen('PHPSESSID='));
            if ($response['status'] === 200 && preg_match('/\A[a-zA-Z0-9,-]+\z/', $sessionId)
                && is_file($testDirectory . '/sessions/sess_' . $sessionId)) {
                return $process;
            }
            usleep(20000);
        } while (microtime(true) < $deadline);
        throw new RuntimeException('Test server did not become ready within 5 seconds.');
    } catch (Throwable $error) {
        stop_server($process);
        throw $error;
    }
}
