<?php

$host = '127.0.0.1:8080';
$url = "http://$host";

echo "Setting up database...\n";
exec('php ' . __DIR__ . '/../scripts/init_db.php');

echo "Starting PHP built-in server...\n";
$cmd = sprintf('php -S %s %s >/dev/null 2>&1 & echo $!', $host, escapeshellarg(__DIR__ . '/../router.php'));
$pid = (int) exec($cmd);
sleep(1);

function get($path, $cookie = '') {
    global $url;
    $options = [
        'http' => [
            'header' => $cookie ? "Cookie: $cookie\r\n" : "",
            'ignore_errors' => true
        ]
    ];
    $context = stream_context_create($options);
    $res = file_get_contents($url . $path, false, $context);
    return ['body' => $res, 'headers' => $http_response_header ?? []];
}

function post($path, $data, $cookie = '') {
    global $url;
    $postdata = http_build_query($data);
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-type: application/x-www-form-urlencoded\r\n" . ($cookie ? "Cookie: $cookie\r\n" : ""),
            'content' => $postdata,
            'ignore_errors' => true
        ]
    ];
    $context = stream_context_create($options);
    $res = file_get_contents($url . $path, false, $context);
    return ['body' => $res, 'headers' => $http_response_header ?? []];
}

function extract_csrf($html) {
    if (preg_match('/name="csrf_token"\s+value="([^"]+)"/', $html, $matches)) {
        return $matches[1];
    }
    return '';
}

function extract_cookie($headers) {
    foreach ($headers as $h) {
        if (preg_match('/^Set-Cookie:\s*(PHPSESSID=[^;]+)/i', $h, $matches)) {
            return $matches[1];
        }
    }
    return '';
}

$failed = 0;
function assert_true($condition, $message) {
    global $failed;
    if (!$condition) {
        echo "FAIL: $message\n";
        $failed++;
    } else {
        echo "PASS: $message\n";
    }
}

try {
    $res = get('/login.php');
    $cookie = extract_cookie($res['headers']);
    $csrf = extract_csrf($res['body']);
    assert_true(strlen($csrf) > 0, "CSRF token generated on login page");

    $res2 = post('/login.php', ['username' => '<script>alert(1)</script>', 'password' => 'wrong', 'csrf_token' => $csrf], $cookie);
    assert_true(strpos((string)$res2['body'], '<script>') === false, "Output encoding prevents XSS in username reflection");
    assert_true(strpos((string)$res2['body'], '&lt;script&gt;') !== false || strpos((string)$res2['body'], 'Invalid username') !== false, "Username properly escaped or rejected");

    // Register User A
    $regRes = get('/register.php');
    $regCookieA = extract_cookie($regRes['headers']);
    $regCsrfA = extract_csrf($regRes['body']);
    post('/register.php', [
        'username' => 'usera',
        'password' => 'pass',
        'csrf_token' => $regCsrfA
    ], $regCookieA);

    // Login User A
    $loginPageA = get('/login.php', $regCookieA);
    $loginCsrfA = extract_csrf($loginPageA['body']);
    $resLoginA = post('/login.php', ['username' => 'usera', 'password' => 'pass', 'csrf_token' => $loginCsrfA], $regCookieA);
    $cookieA = extract_cookie($resLoginA['headers']) ?: $regCookieA;

    // Register User B
    $regResB = get('/register.php');
    $regCookieB = extract_cookie($regResB['headers']);
    $regCsrfB = extract_csrf($regResB['body']);
    post('/register.php', [
        'username' => 'userb',
        'password' => 'pass',
        'csrf_token' => $regCsrfB
    ], $regCookieB);

    // Login User B
    $loginPageB = get('/login.php', $regCookieB);
    $loginCsrfB = extract_csrf($loginPageB['body']);
    $resLoginB = post('/login.php', ['username' => 'userb', 'password' => 'pass', 'csrf_token' => $loginCsrfB], $regCookieB);
    $cookieB = extract_cookie($resLoginB['headers']) ?: $regCookieB;

    // Test Authorization: User A creates a note
    $notesPageA = get('/notes.php', $cookieA);
    $csrfNotesA = extract_csrf($notesPageA['body']);
    post('/notes.php', ['action' => 'create', 'title' => 'User A Note', 'content' => 'Secret', 'csrf_token' => $csrfNotesA], $cookieA);
    
    // Find User A's note ID
    $notesPageA2 = get('/notes.php', $cookieA);
    preg_match('/name="note_id"\s+value="(\d+)"/', $notesPageA2['body'], $matches);
    $noteId = $matches[1] ?? '1';

    // User B tries to delete User A's note
    $notesPageB = get('/notes.php', $cookieB);
    $csrfNotesB = extract_csrf($notesPageB['body']);
    $deleteResB = post('/notes.php', ['action' => 'delete', 'note_id' => $noteId, 'csrf_token' => $csrfNotesB], $cookieB);
    assert_true(strpos((string)$deleteResB['body'], 'Note not found or not permitted') !== false, "Authorization prevents User B from deleting User A's note");

    // Test SQL Injection Resistance: Auth Bypass
    $loginPage = get('/login.php');
    $loginCookie = extract_cookie($loginPage['headers']);
    $loginCsrf = extract_csrf($loginPage['body']);
    $sqliRes = post('/login.php', ['username' => "' OR '1'='1", 'password' => 'wrong', 'csrf_token' => $loginCsrf], $loginCookie);
    assert_true(strpos((string)$sqliRes['body'], 'Invalid username or password') !== false, "SQL parameterization prevents authentication bypass");

    // Test SQL Injection Resistance: Search Metacharacters
    $searchRes = get('/search.php?q=%25', $cookieA);
    assert_true(strpos((string)$searchRes['body'], 'No matching users found') !== false, "SQL LIKE parameterization escapes metacharacters (%) properly");

} finally {
    echo "Stopping server...\n";
    if ($pid > 0) exec("kill $pid");
    if (file_exists(__DIR__ . '/../database/minihack.sqlite')) {
        unlink(__DIR__ . '/../database/minihack.sqlite');
    }
}

if ($failed > 0) {
    echo "$failed tests failed.\n";
    exit(1);
} else {
    echo "All tests passed successfully.\n";
    exit(0);
}
