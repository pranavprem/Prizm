<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function prizm_products(): array
{
    $rows = prizm_db()->query('SELECT * FROM products ORDER BY id')->fetchAll();

    return is_array($rows) ? $rows : [];
}

function prizm_product(int $id): ?array
{
    $stmt = prizm_db()->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    return $row === false ? null : $row;
}

function prizm_catalog_json(): string
{
    $rows = [];
    foreach (prizm_products() as $product) {
        $rows[] = [
            'id' => (int) $product['id'],
            'name' => $product['name'],
            'description' => $product['description'],
            'price' => (float) $product['price'],
            'imgUrl' => $product['imgUrl'],
        ];
    }

    return (string) json_encode($rows, JSON_UNESCAPED_SLASHES);
}

function updateHits(mixed $productId): void
{
    $id = prizm_id($productId);
    if ($id === null) {
        return;
    }

    $stmt = prizm_db()->prepare('UPDATE products SET hits = hits + 1 WHERE id = ?');
    $stmt->execute([$id]);
}

function prizm_current_user(): ?array
{
    prizm_session();
    $id = prizm_id($_SESSION['uid'] ?? null);
    if ($id === null) {
        return null;
    }

    $stmt = prizm_db()->prepare('SELECT id, name, email FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    return $row === false ? null : $row;
}

function prizm_login(string $email, string $password): bool
{
    prizm_session();
    $stmt = prizm_db()->prepare('SELECT id, name, password_hash FROM users WHERE email = ?');
    $stmt->execute([strtolower(trim($email))]);
    $user = $stmt->fetch();
    if ($user === false || !is_string($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $user['id'];
    unset($_SESSION['csrf']);

    return true;
}

function prizm_register(string $name, string $email, string $password): ?string
{
    $name = trim($name);
    $email = strtolower(trim($email));
    if ($name === '' || strlen($name) > 80) {
        return 'Enter a name.';
    }
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        return 'Enter a valid email.';
    }
    if (strlen($password) < 4 || strlen($password) > 200) {
        return 'Use a password of at least 4 characters.';
    }

    $pdo = prizm_db();
    $exists = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $exists->execute([$email]);
    if ($exists->fetch() !== false) {
        return 'That email is already registered.';
    }

    $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
    $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);

    return null;
}

function prizm_logout(): void
{
    prizm_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies') && !headers_sent()) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            (bool) $params['secure'],
            (bool) $params['httponly']
        );
    }
    session_destroy();
}

function prizm_normalize_ids(mixed $raw): array
{
    if (is_array($raw)) {
        $values = $raw;
    } elseif (is_string($raw) && preg_match_all("/'([0-9]+)'/", $raw, $matches) > 0) {
        $values = $matches[1];
    } elseif (is_string($raw)) {
        $values = preg_split('/\s*,\s*/', $raw) ?: [];
    } else {
        $values = [];
    }

    $ids = [];
    foreach ($values as $value) {
        $id = prizm_id($value);
        if ($id !== null && !in_array($id, $ids, true)) {
            $ids[] = $id;
        }
    }

    return $ids;
}

function prizm_cart_ids(): array
{
    prizm_session();
    if (!isset($_SESSION['product_ids'])) {
        return [];
    }

    return prizm_normalize_ids($_SESSION['product_ids']);
}

function prizm_clear_cart(): void
{
    prizm_session();
    unset($_SESSION['product_ids']);
}

function prizm_add_to_cart(mixed $pid): string
{
    prizm_session();
    if (prizm_current_user() === null) {
        return 'Please log in';
    }

    $id = prizm_id($pid);
    if ($id === null || prizm_product($id) === null) {
        return 'Product not found';
    }

    $ids = prizm_cart_ids();
    if (!in_array($id, $ids, true)) {
        $ids[] = $id;
    }
    $_SESSION['product_ids'] = $ids;

    return 'Product added to cart';
}

function prizm_place_order(int $userId, array $ids): ?int
{
    $valid = [];
    foreach (prizm_normalize_ids($ids) as $id) {
        if (prizm_product($id) !== null) {
            $valid[] = $id;
        }
    }
    if ($valid === []) {
        return null;
    }

    $pdo = prizm_db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT INTO orders (userid, created_at) VALUES (?, ?)');
        $stmt->execute([$userId, date('Y-m-d H:i:s')]);
        $orderId = (int) $pdo->lastInsertId();
        $map = $pdo->prepare('INSERT INTO ordermap (orderid, productid) VALUES (?, ?)');
        foreach ($valid as $id) {
            $map->execute([$orderId, $id]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return $orderId > 0 ? $orderId : null;
}

function prizm_recommend(int $userId): array
{
    $sql = 'SELECT DISTINCT p.*
            FROM products p
            INNER JOIN ordermap m ON m.productid = p.id
            INNER JOIN orders o ON o.orderid = m.orderid
            WHERE o.userid = ?
            ORDER BY p.id';
    $stmt = prizm_db()->prepare($sql);
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll();

    return is_array($rows) ? $rows : [];
}

function prizm_add_review(int $productId, int $userId, int $stars, string $review): bool
{
    if ($stars < 1 || $stars > 5 || prizm_product($productId) === null) {
        return false;
    }

    $review = trim($review);
    if (strlen($review) > 2000) {
        $review = substr($review, 0, 2000);
    }

    $stmt = prizm_db()->prepare(
        'INSERT INTO ratings (productid, stars, `timestamp`, userId, review) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$productId, $stars, date('Y-m-d H:i:s'), $userId, $review]);

    return true;
}

function prizm_reviews(int $productId): array
{
    $sql = 'SELECT r.stars, r.review, r.`timestamp` AS reviewed_at, r.userId, u.name
            FROM ratings r
            LEFT JOIN users u ON u.id = r.userId
            WHERE r.productid = ?
            ORDER BY r.id DESC';
    $stmt = prizm_db()->prepare($sql);
    $stmt->execute([$productId]);
    $rows = $stmt->fetchAll();

    return is_array($rows) ? $rows : [];
}

function prizm_rating_summary(int $productId): array
{
    $stmt = prizm_db()->prepare('SELECT COUNT(*) AS n, AVG(stars) AS avg_stars FROM ratings WHERE productid = ?');
    $stmt->execute([$productId]);
    $row = $stmt->fetch();
    if ($row === false) {
        return ['count' => 0, 'average' => null];
    }

    $count = (int) $row['n'];

    return [
        'count' => $count,
        'average' => $count === 0 || $row['avg_stars'] === null ? null : round((float) $row['avg_stars'], 1),
    ];
}

function prizm_peer_urls(): array
{
    prizm_bootstrap();
    $raw = prizm_env('PRIZM_PEER_URLS', '');
    if ($raw === null || trim($raw) === '') {
        return [];
    }

    $urls = [];
    foreach (explode(',', $raw) as $url) {
        $url = trim($url);
        if (filter_var($url, FILTER_VALIDATE_URL) !== false && preg_match('#^https?://#i', $url) === 1) {
            $urls[] = $url;
        }
    }

    return $urls;
}

function prizm_products_from_peer_json(string $json): array
{
    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
        return [];
    }

    $products = [];
    foreach ($decoded as $row) {
        if (!is_array($row) || !isset($row['name'], $row['price']) || !is_numeric($row['price'])) {
            continue;
        }
        $products[] = [
            'id' => $row['id'] ?? 0,
            'name' => (string) $row['name'],
            'description' => (string) ($row['description'] ?? ''),
            'price' => $row['price'],
            'imgUrl' => (string) ($row['imgUrl'] ?? ''),
        ];
    }

    return $products;
}

function prizm_http_get(string $url): ?string
{
    if (!function_exists('curl_init')) {
        return null;
    }

    $ch = curl_init($url);
    if ($ch === false) {
        return null;
    }

    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 4,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_FOLLOWLOCATION => false,
    ];
    if (defined('CURLPROTO_HTTP') && defined('CURLPROTO_HTTPS')) {
        $options[CURLOPT_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
    }
    curl_setopt_array($ch, $options);

    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if (!is_string($body) || $code < 200 || $code >= 300 || strlen($body) > 1000000) {
        return null;
    }

    return $body;
}
