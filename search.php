<?php

declare(strict_types=1);

require_once __DIR__ . '/util.php';

$query = trim((string) ($_GET['q'] ?? ''));
$products = [];
$error = false;
try {
    foreach (prizm_products() as $product) {
        if ($query === ''
            || stripos((string) $product['name'], $query) !== false
            || stripos((string) $product['description'], $query) !== false
        ) {
            $products[] = $product;
        }
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    $error = true;
}

prizm_header('Search - Every One Must Shop', false, 'search', true);
?>
<main id="content" class="container page">
    <p class="eyebrow">Search</p>
    <h1>Find a product</h1>
    <form class="search-form" method="get" action="search.php">
        <label for="autocomplete">Name</label>
        <input id="autocomplete" name="q" value="<?php echo prizm_h($query); ?>" autocomplete="off">
        <button class="btn btn-success" type="submit">Search</button>
    </form>
    <?php if ($error) { ?>
        <p class="form-error">The catalog is unavailable.</p>
    <?php } elseif ($products === []) { ?>
        <p>No matching products.</p>
    <?php } else { ?>
        <ul class="search-results">
            <?php foreach ($products as $product) { ?>
                <li>
                    <a href="displayproduct.php?pid=<?php echo (int) $product['id']; ?>"><?php echo prizm_h($product['name']); ?></a>
                    <span><?php echo prizm_h(prizm_format_price($product['price'])); ?></span>
                </li>
            <?php } ?>
        </ul>
    <?php } ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/jquery-ui-dist@1.13.3/jquery-ui.min.js"></script>
<script>
$(function () {
    $('#autocomplete').autocomplete({
        minLength: 1,
        source: 'products.json.php',
        select: function (event, ui) {
            if (ui.item && ui.item.value) {
                event.preventDefault();
                window.location.href = ui.item.value;
            }
        }
    });
});
</script>
<?php prizm_footer(); ?>
