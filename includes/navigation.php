<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/util.php';

$navMode = $navMode ?? 'inner';
$activeNav = $activeNav ?? '';
$dbError = false;
try {
    $user = prizm_current_user();
} catch (Throwable $e) {
    $user = null;
    $dbError = true;
    error_log($e->getMessage());
}
$count = count(prizm_cart_ids());
$about = $navMode === 'home' ? '#about' : 'index.php#about';
$news = $navMode === 'home' ? '#news' : 'index.php#news';
$contact = $navMode === 'home' ? '#contact' : 'index.php#contact';
?>
<a class="skip-link" href="#content">Skip to content</a>
<header class="site-header">
    <div class="container header-bar">
        <a class="brand" href="index.php">
            <img src="favicon.png" width="36" height="36" alt="">
            <span>
                <strong>Prizm</strong>
                <small>Valar Shopoholics</small>
            </span>
        </a>
        <nav aria-label="Primary">
            <a href="allproducts.php"<?php echo $activeNav === 'catalog' ? ' aria-current="page"' : ''; ?>>Catalog</a>
            <a href="cart.php"<?php echo $activeNav === 'cart' ? ' aria-current="page"' : ''; ?>>Cart
                <span class="cart-count" data-cart-count<?php echo $count === 0 ? ' hidden' : ''; ?>><?php echo $count; ?></span>
            </a>
            <a href="search.php"<?php echo $activeNav === 'search' ? ' aria-current="page"' : ''; ?>>Search</a>
            <a href="<?php echo prizm_h($about); ?>">About</a>
            <a href="<?php echo prizm_h($news); ?>">News</a>
            <a href="<?php echo prizm_h($contact); ?>">Contact</a>
            <?php if ($user === null) { ?>
                <a href="login.php"<?php echo $activeNav === 'login' ? ' aria-current="page"' : ''; ?>>Log in</a>
            <?php } else { ?>
                <span class="who"><?php echo prizm_h($user['name']); ?></span>
                <a href="logout.php">Log out</a>
            <?php } ?>
        </nav>
    </div>
</header>
<div id="cart-status" class="cart-status" role="status"></div>
<?php if ($dbError) { ?>
    <p class="db-banner">The catalog database is not reachable. Check the database settings in the README.</p>
<?php } ?>
