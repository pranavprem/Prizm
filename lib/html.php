<?php

declare(strict_types=1);

require_once __DIR__ . '/catalog.php';

function createProductUI(mixed $id, mixed $img, mixed $desc, mixed $title, mixed $price, bool $linked = true): string
{
    $name = prizm_h($title);
    $copy = prizm_h($desc);
    $src = prizm_h(prizm_safe_url((string) $img));
    $cost = prizm_h(prizm_format_price($price));
    $imgTag = $src === '' ? '' : '<img class="group list-group-image" src="' . $src . '" alt="' . $name . '">';
    $productId = prizm_id($id);
    $linked = $linked && $productId !== null;

    if ($linked) {
        $href = 'displayproduct.php?pid=' . $productId;
        $titleHtml = '<a href="' . $href . '">' . $name . '</a>';
        $media = $imgTag === '' ? '' : '<a href="' . $href . '">' . $imgTag . '</a>';
        $cart = '<button type="button" class="btn btn-success" onclick="addToCart(' . $productId . ')">Add to cart</button>';
        $kicker = '';
    } else {
        $titleHtml = $name;
        $media = $imgTag;
        $cart = '';
        $kicker = '<p class="kicker">Partner catalog</p>';
    }

    return '<div class="col-sm-6 col-md-4">'
        . '<article class="thumbnail prizm-card">'
        . $media
        . '<div class="caption">'
        . $kicker
        . '<h3 class="prizm-card-title">' . $titleHtml . '</h3>'
        . '<p class="prizm-desc">' . $copy . '</p>'
        . '<div class="prizm-card-foot"><p class="price">' . $cost . '</p>' . $cart . '</div>'
        . '</div></article></div>';
}

function createProductThumbnailUI(mixed $id, mixed $img, mixed $desc, mixed $title, mixed $price): string
{
    $productId = prizm_id($id);
    $name = prizm_h($title);
    $src = prizm_h(prizm_safe_url((string) $img));
    $cost = prizm_h(prizm_format_price($price));
    $href = $productId === null ? '' : 'displayproduct.php?pid=' . $productId;
    $titleHtml = $href === '' ? $name : '<a href="' . $href . '">' . $name . '</a>';
    $button = $productId === null
        ? ''
        : '<button type="button" class="btn btn-success" onclick="addToCart(' . $productId . ')">Add to cart</button>';
    $image = $src === '' ? '' : '<img class="group list-group-image2" src="' . $src . '" alt="' . $name . '">';

    return '<div class="col-sm-6 col-md-3">'
        . '<article class="thumbnail prizm-card small-img">'
        . $image
        . '<div class="caption"><h4>' . $titleHtml . '</h4>'
        . '<p class="prizm-desc">' . prizm_h($desc) . '</p>'
        . '<div class="prizm-card-foot"><p class="price">' . $cost . '</p>' . $button . '</div>'
        . '</div></article></div>';
}

function createCartUI(mixed $id, mixed $img, mixed $title, mixed $price, mixed $desc): string
{
    $productId = prizm_id($id);
    $name = prizm_h($title);
    $src = prizm_h(prizm_safe_url((string) $img));
    $href = $productId === null ? '' : 'displayproduct.php?pid=' . $productId;
    $titleHtml = $href === '' ? $name : '<a href="' . $href . '"><h4 class="nomargin">' . $name . '</h4></a>';
    $image = $src === '' ? '' : '<img src="' . $src . '" alt="" class="img-responsive cart-thumb">';

    return '<tr><td data-th="Product"><div class="cart-product">' . $image
        . '<div>' . $titleHtml . '<p>' . prizm_h($desc) . '</p></div></div></td>'
        . '<td data-th="Price" class="text-right">' . prizm_h(prizm_format_price($price)) . '</td></tr>';
}

function createCartTotalUi(mixed $total): string
{
    $formatted = prizm_h(prizm_format_price($total));

    return '<tfoot><tr>'
        . '<td><a href="allproducts.php" class="btn btn-warning">Continue Shopping</a></td>'
        . '<td class="text-right"><strong>Total ' . $formatted . '</strong> '
        . '<a href="checkout.php" class="btn btn-success">Checkout</a></td>'
        . '</tr></tfoot>';
}

function buildProductsUI(mixed $isSingleProduct, mixed $productId = 0): string
{
    if ($isSingleProduct) {
        $id = prizm_id($productId);
        $rows = $id === null ? [] : array_filter([prizm_product($id)]);
    } else {
        $rows = prizm_products();
    }

    $html = '';
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $html .= createProductUI($row['id'], $row['imgUrl'], $row['description'], $row['name'], $row['price']);
    }

    return $html;
}

function buildCartUI(mixed $productIds): array
{
    $ids = prizm_normalize_ids($productIds);
    $html = '';
    $total = 0.0;
    if ($ids === []) {
        return ['html' => '', 'total' => 0.0];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = prizm_db()->prepare('SELECT * FROM products WHERE id IN (' . $placeholders . ')');
    $stmt->execute($ids);
    $byId = [];
    foreach ($stmt->fetchAll() as $row) {
        $byId[(int) $row['id']] = $row;
    }

    foreach ($ids as $id) {
        if (!isset($byId[$id])) {
            continue;
        }
        $row = $byId[$id];
        $html .= createCartUI($row['id'], $row['imgUrl'], $row['name'], $row['price'], $row['description']);
        $total += (float) $row['price'];
    }

    return ['html' => $html, 'total' => $total];
}

function buildAllProducts(): string
{
    $html = '';
    foreach (prizm_peer_urls() as $url) {
        $payload = prizm_http_get($url);
        if ($payload === null) {
            continue;
        }
        foreach (prizm_products_from_peer_json($payload) as $row) {
            $html .= createProductUI($row['id'], $row['imgUrl'], $row['description'], $row['name'], $row['price'], false);
        }
    }

    return $html;
}

function buildRatingStars(mixed $rating, mixed $isMultipleRatings = false, mixed $count = 0, bool $interactive = false): string
{
    $rating = (int) $rating;
    $name = $isMultipleRatings ? 'ratings' . (int) $count : ($interactive ? 'stars' : 'ratings');
    $disabled = $interactive ? '' : ' disabled';
    $html = '<fieldset class="rating"' . $disabled . '>';
    if (!$interactive) {
        $html .= '<legend class="sr-only">Rating</legend>';
    }

    for ($i = 5; $i >= 1; $i--) {
        $id = $name . '-star' . $i;
        $checked = $rating === $i ? ' checked' : '';
        $required = $interactive ? ' required' : '';
        $html .= '<input type="radio" id="' . prizm_h($id) . '" name="' . prizm_h($name) . '" value="' . $i . '"'
            . $checked . $required . '>';
        $html .= '<label class="full" for="' . prizm_h($id) . '">' . $i . ' stars</label>';
    }

    $html .= '</fieldset>';

    return $html;
}

function getJson(string $term = ''): string
{
    $rows = [];
    foreach (prizm_products() as $product) {
        if ($term !== '' && stripos((string) $product['name'], $term) === false) {
            continue;
        }
        $rows[] = [
            'label' => $product['name'],
            'value' => 'displayproduct.php?pid=' . $product['id'],
        ];
    }

    return (string) json_encode($rows, JSON_UNESCAPED_SLASHES);
}

function prizm_header(string $title, bool $homeNav = false, string $active = '', bool $autocomplete = false): void
{
    prizm_session();
    $navMode = $homeNav ? 'home' : 'inner';
    $activeNav = $active;
    $loadAutocomplete = $autocomplete;
    echo '<!DOCTYPE html><html lang="en"><head><title>' . prizm_h($title) . '</title>';
    require prizm_root() . '/includes/head.php';
    echo '</head><body>';
    require prizm_root() . '/includes/navigation.php';
}

function prizm_footer(): void
{
    require prizm_root() . '/includes/footer.php';
    echo '</body></html>';
}
