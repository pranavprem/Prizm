<?php

declare(strict_types=1);

require_once __DIR__ . '/util.php';
prizm_session();

$loginError = null;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!prizm_csrf_ok()) {
        $loginError = 'The request was rejected.';
    } else {
        try {
            $ok = prizm_login((string) ($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''));
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $ok = false;
            $loginError = 'The catalog is unavailable.';
        }
        if ($ok) {
            header('Location: allproducts.php', true, 303);
            return;
        }
        if ($loginError === null) {
            $loginError = 'Email or password does not match.';
        }
    }
}

prizm_header('Log in - Every One Must Shop', false, 'login');
?>
<main id="content" class="container page account-grid">
    <?php require __DIR__ . '/includes/login.php'; ?>
</main>
<?php prizm_footer(); ?>
