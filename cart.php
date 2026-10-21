<?php

declare(strict_types=1);

require_once __DIR__ . '/util.php';
prizm_header('Cart - Every One Must Shop', false, 'cart');
$ids = prizm_cart_ids();
$cart = ['html' => '', 'total' => 0.0];
$error = false;
try {
    if ($ids !== []) {
        $cart = buildCartUI($ids);
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    $error = true;
}
?>
<main id="content" class="container page">
    <p class="eyebrow">Cart</p>
    <h1>Your cart</h1>
    <?php if ($error) { ?>
        <p class="form-error">The cart is unavailable.</p>
    <?php } elseif ($cart['html'] === '') { ?>
        <p>Nothing here yet. <a href="allproducts.php">Browse the catalog</a>.</p>
    <?php } else { ?>
        <table id="cart" class="table table-hover table-condensed">
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="text-right">Price</th>
                </tr>
            </thead>
            <tbody>
                <?php echo $cart['html']; ?>
            </tbody>
            <?php echo createCartTotalUi($cart['total']); ?>
        </table>
    <?php } ?>
</main>
<?php prizm_footer(); ?>
