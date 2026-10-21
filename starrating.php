<?php

declare(strict_types=1);

require_once __DIR__ . '/util.php';
prizm_header('Stars - Every One Must Shop');
?>
<main id="content" class="container page">
    <p class="eyebrow">Stars</p>
    <h1>Rating widget</h1>
    <p>Product pages use this same control. This preview shows four stars.</p>
    <?php echo buildRatingStars(4); ?>
</main>
<?php prizm_footer(); ?>
