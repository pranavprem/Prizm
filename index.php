<?php

declare(strict_types=1);

require_once __DIR__ . '/util.php';
prizm_session();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Prizm</title>
    <?php require __DIR__ . '/includes/head.php'; ?>
</head>
<body>
<?php
$navMode = 'home';
$activeNav = 'home';
require __DIR__ . '/includes/navigation.php';
?>
<main id="content">
    <?php require __DIR__ . '/includes/home.php'; ?>
    <?php require __DIR__ . '/includes/about.php'; ?>
    <?php require __DIR__ . '/includes/news.php'; ?>
    <?php require __DIR__ . '/includes/contacts.php'; ?>
    <section class="band">
        <div class="container account-grid">
            <?php require __DIR__ . '/includes/login.php'; ?>
            <?php require __DIR__ . '/includes/newuser.php'; ?>
        </div>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
