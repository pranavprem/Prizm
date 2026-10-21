<section class="account-card" id="login">
    <h2>Log in</h2>
    <?php if (!empty($loginError)) { ?>
        <p class="form-error"><?php echo prizm_h($loginError); ?></p>
    <?php } ?>
    <?php
    $signedIn = null;
    try {
        $signedIn = prizm_current_user();
    } catch (Throwable $e) {
        error_log($e->getMessage());
    }
    if ($signedIn !== null) { ?>
        <p>You are signed in as <?php echo prizm_h($signedIn['name']); ?>.</p>
        <p><a href="logout.php">Log out</a></p>
    <?php } else { ?>
        <form method="post" action="login.php">
            <input type="hidden" name="csrf" value="<?php echo prizm_h(prizm_csrf_token()); ?>">
            <label for="login-email">Email</label>
            <input id="login-email" name="email" type="email" autocomplete="username" required>
            <label for="login-password">Password</label>
            <input id="login-password" name="password" type="password" autocomplete="current-password" required>
            <button class="btn btn-success" type="submit">Log in</button>
        </form>
        <p class="hint">A sample shopper is created on the first run. The email and password are in the README.</p>
    <?php } ?>
</section>
