<section class="hero" id="home">
    <div class="container">
        <p class="eyebrow">Every One Must Shop</p>
        <h1>One catalog for the whole team.</h1>
        <p class="lede">Prizm is the shared storefront for the Valar Shopoholics marketplaces. Browse what is on the table, keep a cart, and leave a review.</p>
        <a class="btn btn-lg btn-success" href="allproducts.php">Browse the catalog</a>
    </div>
</section>
<?php
$featured = [];
try {
    $featured = array_slice(prizm_products(), 0, 3);
} catch (Throwable $e) {
    error_log($e->getMessage());
}
if ($featured !== []) {
    echo '<section class="band"><div class="container"><div id="products" class="row">';
    foreach ($featured as $row) {
        echo createProductUI($row['id'], $row['imgUrl'], $row['description'], $row['name'], $row['price']);
    }
    echo '</div></div></section>';
}
