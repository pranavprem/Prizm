<?php

declare(strict_types=1);

require_once __DIR__ . '/util.php';

function FillDetails(): void
{
    echo buildProductsUI(false, 0);
    echo buildAllProducts();
}

prizm_header('Catalog - Every One Must Shop', false, 'catalog');
?>
<main id="content" class="container page">
    <p class="eyebrow">Catalog</p>
    <h1>Everything on the table</h1>
    <div id="products" class="row list-group">
        <?php
        try {
            FillDetails();
        } catch (Throwable $e) {
            error_log($e->getMessage());
            echo '<p class="form-error">The catalog is unavailable.</p>';
        }
?>
    </div>
</main>
<?php prizm_footer(); ?>
