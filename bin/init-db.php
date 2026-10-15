<?php

declare(strict_types=1);

require dirname(__DIR__) . '/util.php';

$pdo = prizm_db();
$products = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$driver = (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
fwrite(STDOUT, "Database ready ({$driver}). Products: {$products}\n");
