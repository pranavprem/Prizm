<?php

declare(strict_types=1);

/**
 * Environment, database connection, schema, and the local sample catalog.
 *
 * The 2017 code opened MySQL with the removed mysql_* API and hard-coded
 * root / an empty password in user_recommend.php. Credentials now come only
 * from the environment.
 */
final class PrizmApp
{
    public static ?PDO $pdo = null;

    public static bool $booted = false;
}

function prizm_root(): string
{
    return dirname(__DIR__);
}

function prizm_load_env(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $eq = strpos($line, '=');
        if ($eq === false) {
            continue;
        }

        $key = trim(substr($line, 0, $eq));
        $value = trim(substr($line, $eq + 1));
        if ($key === '' || getenv($key) !== false) {
            continue;
        }

        if (strlen($value) >= 2) {
            $quote = $value[0];
            if (($quote === '"' || $quote === "'") && str_ends_with($value, $quote)) {
                $value = substr($value, 1, -1);
            }
        }

        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
    }
}

function prizm_env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }

    return $value;
}

function prizm_bootstrap(): void
{
    if (PrizmApp::$booted) {
        return;
    }

    PrizmApp::$booted = true;
    prizm_load_env(prizm_root() . '/.env');
}

function prizm_reset(): void
{
    PrizmApp::$pdo = null;
    PrizmApp::$booted = false;
}

function prizm_db(): PDO
{
    if (PrizmApp::$pdo instanceof PDO) {
        return PrizmApp::$pdo;
    }

    prizm_bootstrap();
    $driver = prizm_env('PRIZM_DB_DRIVER', 'sqlite');

    try {
        if ($driver === 'mysql') {
            $host = prizm_env('PRIZM_DB_HOST', '127.0.0.1');
            $port = prizm_env('PRIZM_DB_PORT', '3306');
            $name = prizm_env('PRIZM_DB_NAME', '272project');
            $user = prizm_env('PRIZM_DB_USER', 'root');
            $pass = prizm_env('PRIZM_DB_PASS', '');
            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
            $pdo = new PDO($dsn, (string) $user, (string) $pass);
        } else {
            $path = prizm_env('PRIZM_DB_PATH', 'data/prizm.sqlite');
            if ($path !== ':memory:' && !str_starts_with((string) $path, '/')) {
                $path = prizm_root() . '/' . $path;
            }
            if ($path !== ':memory:') {
                $dir = dirname((string) $path);
                if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
                    throw new RuntimeException('Could not create the database directory.');
                }
            }
            $pdo = new PDO('sqlite:' . $path);
            $pdo->exec('PRAGMA foreign_keys = ON');
            $pdo->exec('PRAGMA busy_timeout = 3000');
        }
    } catch (PDOException $e) {
        throw new RuntimeException('Could not connect to the Prizm database. Check PRIZM_DB_* in .env.example.', 0, $e);
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    prizm_migrate($pdo);
    if (prizm_env('PRIZM_SEED', '1') !== '0') {
        prizm_seed($pdo);
    }

    PrizmApp::$pdo = $pdo;

    return $pdo;
}

function prizm_migrate(PDO $pdo): void
{
    $driver = (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'mysql') {
        prizm_migrate_mysql($pdo);
        return;
    }

    prizm_migrate_sqlite($pdo);
}

function prizm_migrate_sqlite(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL
        )'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY,
            name TEXT NOT NULL,
            description TEXT NOT NULL,
            price REAL NOT NULL,
            imgUrl TEXT NOT NULL,
            hits INTEGER NOT NULL DEFAULT 0
        )'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS orders (
            orderid INTEGER PRIMARY KEY,
            userid INTEGER NOT NULL,
            created_at TEXT NOT NULL,
            FOREIGN KEY (userid) REFERENCES users(id)
        )'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS ordermap (
            orderid INTEGER NOT NULL,
            productid INTEGER NOT NULL,
            PRIMARY KEY (orderid, productid),
            FOREIGN KEY (orderid) REFERENCES orders(orderid),
            FOREIGN KEY (productid) REFERENCES products(id)
        )'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS ratings (
            id INTEGER PRIMARY KEY,
            productid INTEGER NOT NULL,
            stars INTEGER NOT NULL CHECK (stars BETWEEN 1 AND 5),
            timestamp TEXT NOT NULL,
            userId INTEGER NOT NULL,
            review TEXT NOT NULL,
            FOREIGN KEY (productid) REFERENCES products(id),
            FOREIGN KEY (userId) REFERENCES users(id)
        )'
    );
}

function prizm_migrate_mysql(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS users (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(80) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS products (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            imgUrl VARCHAR(1024) NOT NULL,
            hits INT NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS orders (
            orderid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            userid INT NOT NULL,
            created_at DATETIME NOT NULL,
            FOREIGN KEY (userid) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS ordermap (
            orderid INT NOT NULL,
            productid INT NOT NULL,
            PRIMARY KEY (orderid, productid),
            FOREIGN KEY (orderid) REFERENCES orders(orderid),
            FOREIGN KEY (productid) REFERENCES products(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS ratings (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            productid INT NOT NULL,
            stars INT NOT NULL,
            `timestamp` DATETIME NOT NULL,
            userId INT NOT NULL,
            review TEXT NOT NULL,
            CHECK (stars BETWEEN 1 AND 5),
            FOREIGN KEY (productid) REFERENCES products(id),
            FOREIGN KEY (userId) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
}

function prizm_seed(PDO $pdo): void
{
    $count = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $products = [
        [1, 'Prism Notebook', 'A5 hardcover, ruled, 160 pages. The cover is the same violet as the mark.', 14, 'images/notebook.svg', 4],
        [2, 'Indigo Mug', '12 oz ceramic. The glaze is dark enough to hide Tuesday\'s coffee.', 11, 'images/mug.svg', 2],
        [3, 'Canvas Tote', 'Heavy canvas, flat bottom, long handles. Holds a laptop and a loaf of bread.', 18, 'images/tote.svg', 1],
        [4, 'Brass Desk Lamp', 'Short arm, heavy base, warm bulb. Meant for a small desk.', 32, 'images/lamp.svg', 3],
        [5, 'Field Pencil Set', 'Six graphite pencils and a small sharpener.', 8, 'images/pencils.svg', 0],
        [6, 'Glass Bottle', '18 oz glass with a leak-resistant cap.', 16, 'images/bottle.svg', 1],
    ];

    $pdo->beginTransaction();
    try {
        $insert = $pdo->prepare('INSERT INTO products (id, name, description, price, imgUrl, hits) VALUES (?, ?, ?, ?, ?, ?)');
        foreach ($products as $product) {
            $insert->execute($product);
        }

        $email = strtolower((string) prizm_env('PRIZM_DEMO_EMAIL', 'demo@prizm.test'));
        $password = (string) prizm_env('PRIZM_DEMO_PASSWORD', 'demo');
        $user = $pdo->prepare('INSERT INTO users (id, name, email, password_hash) VALUES (1, ?, ?, ?)');
        $user->execute(['Demo Shopper', $email, password_hash($password, PASSWORD_DEFAULT)]);

        $pdo->prepare('INSERT INTO orders (orderid, userid, created_at) VALUES (1, 1, ?)')
            ->execute(['2017-10-25 19:13:09']);
        $pdo->prepare('INSERT INTO ordermap (orderid, productid) VALUES (1, 1)')->execute();
        $pdo->prepare('INSERT INTO ordermap (orderid, productid) VALUES (1, 4)')->execute();
        $pdo->prepare('INSERT INTO ratings (productid, stars, `timestamp`, userId, review) VALUES (1, 4, ?, 1, ?)')
            ->execute([
                '2017-10-25 20:00:00',
                'The paper holds up and the cover does not scuff in a backpack.',
            ]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function prizm_is_public_path(string $path): bool
{
    $path = rawurldecode($path);
    if ($path === '' || ($path[0] ?? '') !== '/') {
        $path = '/' . ltrim($path, '/');
    }
    $path = preg_replace('#/+#', '/', $path) ?? $path;

    if (str_contains($path, "\0") || str_contains($path, '..')) {
        return false;
    }
    if ($path === '/') {
        return true;
    }

    foreach (['/data', '/lib', '/tests', '/sql', '/bin', '/vendor', '/docs'] as $blocked) {
        if ($path === $blocked || str_starts_with($path, $blocked . '/')) {
            return false;
        }
    }

    $base = basename($path);
    if ($base === '' || str_starts_with($base, '.')) {
        return false;
    }

    $hidden = [
        'router.php',
        'util.php',
        'config.php',
        'composer.json',
        'composer.lock',
        'phpunit.xml',
        'phpunit.xml.dist',
    ];
    if (in_array($base, $hidden, true)) {
        return false;
    }

    $ext = strtolower(pathinfo($base, PATHINFO_EXTENSION));

    return in_array($ext, ['php', 'css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'html', 'webp'], true);
}

function prizm_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function prizm_csrf_token(): string
{
    prizm_session();
    if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }

    return $_SESSION['csrf'];
}

function prizm_csrf_ok(): bool
{
    prizm_session();
    $sent = $_POST['csrf'] ?? $_GET['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($sent) || $sent === '' || empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
        return false;
    }

    return hash_equals($_SESSION['csrf'], $sent);
}

function prizm_h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function prizm_format_price(mixed $price): string
{
    return '$' . number_format((float) $price, 2, '.', ',');
}

function prizm_id(mixed $value): ?int
{
    if (is_int($value) && $value > 0) {
        return $value;
    }
    if (is_string($value) && preg_match('/^[1-9][0-9]*$/', $value) === 1) {
        return (int) $value;
    }

    return null;
}

function prizm_safe_url(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }
    if (preg_match('#^https://#i', $url) === 1 || preg_match('#^http://#i', $url) === 1) {
        return $url;
    }
    if (preg_match('#^(?:images|try)/[A-Za-z0-9._/-]+$#', $url) === 1) {
        return $url;
    }
    if (preg_match('#^[A-Za-z0-9][A-Za-z0-9._/-]*$#', $url) === 1 && !str_contains($url, '..')) {
        return $url;
    }

    return '';
}
