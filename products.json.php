<?php

declare(strict_types=1);

require_once __DIR__ . '/util.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    echo getJson(trim((string) ($_GET['term'] ?? '')));
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo '[]';
}
