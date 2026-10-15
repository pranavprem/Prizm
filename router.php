<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (!is_string($path) || $path === '') {
    $path = '/';
}

if (!prizm_is_public_path($path)) {
    http_response_code(404);
    echo 'Not found';
    return true;
}

if ($path === '/' || is_file(__DIR__ . $path)) {
    return false;
}

http_response_code(404);
echo 'Not found';
return true;
