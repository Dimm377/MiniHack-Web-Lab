<?php

declare(strict_types=1);

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestPath = is_string($requestPath) ? rawurldecode($requestPath) : '/';
if (
    str_contains($requestPath, "\0")
    || preg_match('#(?:^|/)\.\.(?:/|$)#', $requestPath) === 1
    || preg_match('#(?:^|/)\.[^/]+(?:/|$)#', $requestPath) === 1
    || preg_match('#^/(?:config|database|includes|scripts)(?:/|$)#i', $requestPath) === 1
) {
    http_response_code(404);
    echo 'Not found.';
    return true;
}
$file = __DIR__ . $requestPath;
if ($requestPath !== '/' && is_file($file)) {
    return false;
}
if ($requestPath === '/') {
    require __DIR__ . '/index.php';
    return true;
}
return false;
