<?php

declare(strict_types=1);

require_once __DIR__ . '/util.php';

$uid = prizm_id($_GET['uid'] ?? null);
$rows = [];
$error = null;
try {
    if ($uid === null) {
        $error = 'Add a user id, for example user_recommend.php?uid=1.';
    } else {
        $rows = prizm_recommend($uid);
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    $error = 'Recommendations are unavailable.';
}

prizm_header('Recommendations - Every One Must Shop');
?>
<main id="content" class="container page">
    <p class="eyebrow">Recommendations</p>
    <h1>From past orders</h1>
    <?php if ($error !== null) { ?>
        <p class="form-error"><?php echo prizm_h($error); ?></p>
    <?php } elseif ($rows === []) { ?>
        <p>No recommendations yet.</p>
    <?php } else { ?>
        <div id="products" class="row">
            <?php
            foreach ($rows as $row) {
                echo createProductUI($row['id'], $row['imgUrl'], $row['description'], $row['name'], $row['price']);
            }
        ?>
        </div>
    <?php } ?>
</main>
<?php prizm_footer(); ?>
