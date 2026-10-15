<?php

declare(strict_types=1);

require_once __DIR__ . '/util.php';

$id = prizm_id($_GET['pid'] ?? null);
$product = null;
$reviews = [];
$summary = ['count' => 0, 'average' => null];
$error = null;

try {
    if ($id === null) {
        http_response_code(404);
        $error = 'That product is not in the catalog.';
    } else {
        updateHits($id);
        $product = prizm_product($id);
        if ($product === null) {
            http_response_code(404);
            $error = 'That product is not in the catalog.';
        } else {
            $reviews = prizm_reviews($id);
            $summary = prizm_rating_summary($id);
        }
    }
    $user = prizm_current_user();
} catch (Throwable $e) {
    error_log($e->getMessage());
    $user = null;
    $error = 'The catalog is unavailable.';
}

$title = $product === null ? 'Product' : (string) $product['name'];
prizm_header($title . ' - Every One Must Shop', false, 'catalog');
?>
<main id="content" class="container page">
    <?php if ($error !== null || $product === null) { ?>
        <h1>Product</h1>
        <p class="form-error"><?php echo prizm_h($error ?? 'That product is not in the catalog.'); ?></p>
    <?php } else { ?>
        <article class="product-detail">
            <div class="product-media">
                <?php $src = prizm_safe_url((string) $product['imgUrl']); ?>
                <?php if ($src !== '') { ?>
                    <img src="<?php echo prizm_h($src); ?>" alt="<?php echo prizm_h($product['name']); ?>">
                <?php } ?>
            </div>
            <div>
                <p class="eyebrow">Catalog</p>
                <h1><?php echo prizm_h($product['name']); ?></h1>
                <p class="price price-lg"><?php echo prizm_h(prizm_format_price($product['price'])); ?></p>
                <p><?php echo prizm_h($product['description']); ?></p>
                <p class="meta">Viewed <?php echo (int) $product['hits']; ?> times</p>
                <button type="button" class="btn btn-success" onclick="addToCart(<?php echo (int) $product['id']; ?>)">Add to cart</button>
            </div>
        </article>

        <section class="reviews">
            <h2>Reviews</h2>
            <?php if ($summary['count'] === 0) { ?>
                <p>No reviews yet.</p>
            <?php } else { ?>
                <div class="review-summary">
                    <?php echo buildRatingStars((int) round((float) $summary['average'])); ?>
                    <p><?php echo prizm_h((string) $summary['average']); ?> out of 5, from <?php echo (int) $summary['count']; ?> review<?php echo $summary['count'] === 1 ? '' : 's'; ?>.</p>
                </div>
            <?php } ?>

            <ul class="review-list">
                <?php foreach ($reviews as $review) { ?>
                    <li>
                        <strong><?php echo prizm_h($review['name'] ?: 'Shopper'); ?></strong>
                        <span><?php echo (int) $review['stars']; ?> / 5</span>
                        <p><?php echo nl2br(prizm_h($review['review']), false); ?></p>
                        <time><?php echo prizm_h($review['reviewed_at']); ?></time>
                    </li>
                <?php } ?>
            </ul>

            <?php if ($user === null) { ?>
                <p><a href="login.php">Log in</a> to add a review.</p>
            <?php } else { ?>
                <form class="review-form" method="post" action="newreview.php">
                    <input type="hidden" name="csrf" value="<?php echo prizm_h(prizm_csrf_token()); ?>">
                    <input type="hidden" name="pid" value="<?php echo (int) $product['id']; ?>">
                    <input type="hidden" name="redirect" value="1">
                    <label for="stars-star5">Your rating</label>
                    <?php echo buildRatingStars(0, false, 0, true); ?>
                    <label for="review-text">Review</label>
                    <textarea id="review-text" name="review" rows="4" maxlength="2000"></textarea>
                    <button class="btn btn-success" type="submit">Save review</button>
                </form>
            <?php } ?>
        </section>
    <?php } ?>
</main>
<?php prizm_footer(); ?>
