<?php

declare(strict_types=1);

require_once __DIR__ . '/util.php';
prizm_session();

$notice = null;
$error = null;
$placed = prizm_id($_GET['order'] ?? null);

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!prizm_csrf_ok()) {
        $error = 'The request was rejected.';
    } else {
        try {
            $user = prizm_current_user();
            if ($user === null) {
                $error = 'Please log in';
            } else {
                $orderId = prizm_place_order((int) $user['id'], prizm_cart_ids());
                if ($orderId === null) {
                    $error = 'The cart is empty.';
                } else {
                    prizm_clear_cart();
                    header('Location: checkout.php?order=' . $orderId, true, 303);
                    return;
                }
            }
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $error = 'The order was not recorded.';
        }
    }
}

prizm_header('Checkout - Every One Must Shop', false, 'cart');
$user = null;
$cart = ['html' => '', 'total' => 0.0];
try {
    $user = prizm_current_user();
    if ($user !== null && $placed === null) {
        $cart = buildCartUI(prizm_cart_ids());
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    $error = $error ?? 'The catalog is unavailable.';
}
?>
<main id="content" class="container page narrow">
    <p class="eyebrow">Checkout</p>
    <h1>Record this order</h1>
    <p class="notice">PayPal is not connected. This page writes the cart into the local order tables so recommendations can see it. Nothing is charged.</p>
    <?php if ($error !== null) { ?>
        <p class="form-error"><?php echo prizm_h($error); ?></p>
    <?php } ?>
    <?php if ($placed !== null) { ?>
        <p>Order <?php echo $placed; ?> is recorded.</p>
        <p>
            <?php if ($user !== null) { ?>
                <a href="user_recommend.php?uid=<?php echo (int) $user['id']; ?>">See recommendations</a>
                or
            <?php } ?>
            <a href="allproducts.php">keep shopping</a>.
        </p>
    <?php } elseif ($user === null) { ?>
        <p><a href="login.php">Log in</a> before recording an order.</p>
    <?php } elseif ($cart['html'] === '') { ?>
        <p>The cart is empty. <a href="allproducts.php">Browse the catalog</a>.</p>
    <?php } else { ?>
        <table class="table">
            <tbody><?php echo $cart['html']; ?></tbody>
        </table>
        <p><strong>Total <?php echo prizm_h(prizm_format_price($cart['total'])); ?></strong></p>
        <form method="post" action="checkout.php">
            <input type="hidden" name="csrf" value="<?php echo prizm_h(prizm_csrf_token()); ?>">
            <button class="btn btn-success" type="submit">Record this order</button>
        </form>
    <?php } ?>
</main>
<?php prizm_footer(); ?>
