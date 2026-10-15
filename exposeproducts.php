<?php

declare(strict_types=1);

require_once __DIR__ . '/util.php';
header('Content-Type: application/json; charset=utf-8');

try {
    echo prizm_catalog_json();
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo '[]';
}
