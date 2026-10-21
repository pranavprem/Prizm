<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PrizmTest extends TestCase
{
    protected function setUp(): void
    {
        prizm_reset();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION = [];
        $_POST = [];
        $_GET = [];
        $this->emptyTables();
    }

    protected function tearDown(): void
    {
        putenv('PRIZM_SEED=0');
    }

    public function testPriceFormattingDoesNotDoubleTheCents(): void
    {
        $this->assertSame('$19.99', prizm_format_price(19.99));
        $this->assertSame('$12.00', prizm_format_price(12));
        $this->assertSame('$1,200.00', prizm_format_price(1200));
    }

    public function testProductMarkupEscapesTextAndKeepsASinglePrice(): void
    {
        $html = createProductUI(1, 'javascript:alert(1)', 'A "quote"', '<script>', 19.99);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringContainsString('$19.99', $html);
        $this->assertStringNotContainsString('19.99.00', $html);
        $this->assertStringContainsString('displayproduct.php?pid=1', $html);
    }

    public function testPartnerCardsAreNotAddedToTheLocalCart(): void
    {
        $html = createProductUI(4, 'https://shop.example/hat.png', 'Wool', 'Hat', 5, false);

        $this->assertStringContainsString('Partner catalog', $html);
        $this->assertStringContainsString('https://shop.example/hat.png', $html);
        $this->assertStringNotContainsString('addToCart', $html);
        $this->assertStringNotContainsString('displayproduct.php', $html);
    }

    public function testStarsMarkOnlyTheChosenValue(): void
    {
        $html = buildRatingStars(4);

        $this->assertMatchesRegularExpression('/id="ratings-star4"[^>]*checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/id="ratings-star3"[^>]*checked/', $html);
        $this->assertStringContainsString('disabled', $html);
    }

    public function testSingleProductQueryDoesNotLeakTheRestOfTheCatalog(): void
    {
        $this->addProduct(1, 'One');
        $this->addProduct(2, 'Two');

        $html = buildProductsUI(true, 2);
        $this->assertStringContainsString('Two', $html);
        $this->assertStringNotContainsString('>One<', $html);
        $this->assertSame('', buildProductsUI(true, '2 OR 1=1'));
    }

    public function testLegacyCartStringAndInjectionStayInsidePlaceholders(): void
    {
        $this->addProduct(1, 'Notebook', 14);
        $this->addProduct(2, 'Mug', 11);
        $this->logIn();

        $this->assertSame('Product added to cart', prizm_add_to_cart(1));
        $this->assertSame('Product added to cart', prizm_add_to_cart(1));
        $this->assertSame([1], prizm_cart_ids());

        $_SESSION['product_ids'] = "'2','1'";
        $this->assertSame([2, 1], prizm_cart_ids());

        $cart = buildCartUI("'2','1' OR 1=1");
        $this->assertSame(25.0, $cart['total']);
        $this->assertStringContainsString('Mug', $cart['html']);

        $this->assertSame('Product not found', prizm_add_to_cart('1; DROP TABLE products'));
        $this->assertNotNull(prizm_product(1));
    }

    public function testCartRequiresARealLogin(): void
    {
        $this->addProduct(1, 'Notebook');

        $this->assertSame('Please log in', prizm_add_to_cart(1));
        $this->assertSame([], prizm_cart_ids());
    }

    public function testOrdersDriveRecommendationsAndReviews(): void
    {
        $this->addProduct(1, 'Notebook', 14);
        $this->addProduct(2, 'Mug', 11);
        $userId = $this->logIn();

        prizm_add_to_cart(2);
        $orderId = prizm_place_order($userId, prizm_cart_ids());
        $this->assertNotNull($orderId);

        $names = array_column(prizm_recommend($userId), 'name');
        $this->assertSame(['Mug'], $names);
        $this->assertSame([], prizm_recommend(999));
        $this->assertNull(prizm_id('1 OR 1=1'));

        $this->assertTrue(prizm_add_review(2, $userId, 5, "Warm\nand heavy"));
        $this->assertFalse(prizm_add_review(2, $userId, 9, 'nope'));
        $reviews = prizm_reviews(2);
        $this->assertSame("Warm\nand heavy", $reviews[0]['review']);
        $this->assertSame(5, (int) $reviews[0]['stars']);
        $this->assertSame(5.0, prizm_rating_summary(2)['average']);
    }

    public function testSearchJsonPointsAtTheProductPage(): void
    {
        $this->addProduct(7, 'Brass Lamp');
        $this->addProduct(8, 'Tote');

        $matches = json_decode(getJson('brass'), true);
        $this->assertCount(1, $matches);
        $this->assertSame('displayproduct.php?pid=7', $matches[0]['value']);
        $this->assertSame('Brass Lamp', $matches[0]['label']);
        $this->assertSame([], json_decode(getJson('missing'), true));
    }

    public function testPeerPayloadDropsMalformedRows(): void
    {
        $rows = prizm_products_from_peer_json('[{"name":"Hat","price":"5"},{"nope":true},"x"]');

        $this->assertCount(1, $rows);
        $this->assertSame('Hat', $rows[0]['name']);
        $this->assertSame([], prizm_peer_urls());
        $this->assertSame('', buildAllProducts());
    }

    public function testHitsIncrementForARealProductOnly(): void
    {
        $this->addProduct(1, 'Notebook');
        updateHits(1);
        updateHits('nope');

        $this->assertSame(1, (int) prizm_product(1)['hits']);
    }

    public function testLoginRejectsTheWrongPassword(): void
    {
        $this->logIn('ada@example.com', 'secret');

        $this->assertNotNull(prizm_current_user());
        prizm_logout();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $this->assertFalse(prizm_login('ada@example.com', 'wrong'));
        $this->assertNull(prizm_current_user());
        $this->assertSame('That email is already registered.', prizm_register('Ada', 'ada@example.com', 'secret'));
    }

    public function testCsrfTokenAndPublicPaths(): void
    {
        $_POST['csrf'] = prizm_csrf_token();
        $this->assertTrue(prizm_csrf_ok());
        $_POST['csrf'] = 'not-the-token';
        $this->assertFalse(prizm_csrf_ok());

        $this->assertTrue(prizm_is_public_path('/css/site.css'));
        $this->assertTrue(prizm_is_public_path('/'));
        $this->assertFalse(prizm_is_public_path('/data/prizm.sqlite'));
        $this->assertFalse(prizm_is_public_path('/.env'));
        $this->assertFalse(prizm_is_public_path('/lib/bootstrap.php'));
        $this->assertFalse(prizm_is_public_path('/../.env'));
    }

    public function testSampleCatalogSeedsOnce(): void
    {
        putenv('PRIZM_SEED=1');
        prizm_reset();
        $pdo = prizm_db();

        $this->assertSame(6, (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn());
        $this->assertSame(1, (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn());
        prizm_seed($pdo);
        $this->assertSame(6, (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn());

        putenv('PRIZM_SEED=0');
    }

    public function testPhpFilesDoNotCallTheRemovedMysqlExtension(): void
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__)));
        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $path = $file->getPathname();
            if (str_contains($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)) {
                continue;
            }
            $code = file_get_contents($path);
            $this->assertIsString($code);
            $this->assertDoesNotMatchRegularExpression('/\bmysql_(query|connect|fetch|select_db|error)\s*\(/', $code, $path);
        }
    }

    private function emptyTables(): void
    {
        $pdo = prizm_db();
        $driver = (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'mysql') {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        } else {
            $pdo->exec('PRAGMA foreign_keys = OFF');
        }
        foreach (['ratings', 'ordermap', 'orders', 'products', 'users'] as $table) {
            $pdo->exec('DELETE FROM ' . $table);
        }
        if ($driver === 'mysql') {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        } else {
            $pdo->exec('PRAGMA foreign_keys = ON');
        }
    }

    private function addProduct(int $id, string $name, float $price = 10): void
    {
        prizm_db()->prepare('INSERT INTO products (id, name, description, price, imgUrl, hits) VALUES (?, ?, ?, ?, ?, 0)')
            ->execute([$id, $name, 'A short description.', $price, 'images/notebook.svg']);
    }

    private function logIn(string $email = 'ada@example.com', string $password = 'secret'): int
    {
        $error = prizm_register('Ada', $email, $password);
        $this->assertNull($error);
        $this->assertTrue(prizm_login($email, $password));
        $id = prizm_id($_SESSION['uid'] ?? null);
        $this->assertNotNull($id);

        return $id;
    }
}
