<?php

declare(strict_types=1);

require_once __DIR__ . '/util.php';
prizm_session();
header('Cache-Control: no-store');

if (!isset($_POST['pid']) && !isset($_GET['pid'])) {
    http_response_code(400);
    echo 'Missing product.';
    return;
}

if (!prizm_csrf_ok()) {
    http_response_code(400);
    echo 'The request was rejected.';
    return;
}

try {
    $message = prizm_add_to_cart($_POST['pid'] ?? $_GET['pid']);
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo 'The catalog is unavailable.';
    return;
}

header('X-Cart-Count: ' . count(prizm_cart_ids()));
echo $message;
