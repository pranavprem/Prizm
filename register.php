<?php

declare(strict_types=1);

require_once __DIR__ . '/util.php';
prizm_session();

$registerError = null;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!prizm_csrf_ok()) {
        $registerError = 'The request was rejected.';
    } else {
        try {
            $registerError = prizm_register(
                (string) ($_POST['name'] ?? ''),
                (string) ($_POST['email'] ?? ''),
                (string) ($_POST['password'] ?? '')
            );
            if ($registerError === null && prizm_login((string) ($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''))) {
                header('Location: allproducts.php', true, 303);
                return;
            }
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $registerError = 'The catalog is unavailable.';
        }
    }
}

prizm_header('Register - Every One Must Shop', false, 'login');
?>
<main id="content" class="container page account-grid">
    <?php require __DIR__ . '/includes/newuser.php'; ?>
</main>
<?php prizm_footer(); ?>
