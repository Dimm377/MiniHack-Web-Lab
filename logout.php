<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';

if (!is_post()) {
    header('Allow: POST');
    http_response_code(405);
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><title>Method not allowed</title>';
    echo '<body><h1>Method not allowed</h1><p>Use POST to log out.</p></body></html>';
    exit;
}
verify_csrf_or_abort();
log_out();
redirect('/');
