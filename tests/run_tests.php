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

    $regRes = get('/register.php');
    $regCookie = extract_cookie($regRes['headers']);
    $regCsrf = extract_csrf($regRes['body']);
    $res3 = post('/register.php', [
        'username' => 'testuser',
        'password' => 'testpass',
        'csrf_token' => $regCsrf
    ], $regCookie);
    assert_true(strpos(implode("\n", $res3['headers']), 'Location: /login.php') !== false, "Registration redirects to login");

    $res4 = post('/login.php', ['username' => 'testuser', 'password' => 'testpass', 'csrf_token' => 'invalid'], $regCookie);
    assert_true(strpos($res4['headers'][0] ?? '', '403') !== false, "Invalid CSRF token results in 403 Forbidden");

    $loginPage = get('/login.php', $regCookie);
    $loginCsrf = extract_csrf($loginPage['body']);
    $res5 = post('/login.php', ['username' => 'testuser', 'password' => 'testpass', 'csrf_token' => $loginCsrf], $regCookie);
    $sessionCookie = extract_cookie($res5['headers']);
    if (!$sessionCookie) $sessionCookie = $regCookie;
    assert_true(strpos(implode("\n", $res5['headers']), 'Location: /profile.php') !== false, "Valid login redirects to profile");

    $profileRes = get('/profile.php', $sessionCookie);
    assert_true(strpos((string)$profileRes['body'], 'testuser') !== false, "Profile loads and shows username");

    $unauthRes = get('/profile.php');
    assert_true(strpos(implode("\n", $unauthRes['headers']), 'Location: /login.php') !== false, "Unauthenticated access redirects to login");

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
