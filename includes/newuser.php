<section class="account-card" id="register">
    <h2>Create an account</h2>
    <?php if (!empty($registerError)) { ?>
        <p class="form-error"><?php echo prizm_h($registerError); ?></p>
    <?php } ?>
    <form method="post" action="register.php">
        <input type="hidden" name="csrf" value="<?php echo prizm_h(prizm_csrf_token()); ?>">
        <label for="register-name">Name</label>
        <input id="register-name" name="name" type="text" autocomplete="name" maxlength="80" required>
        <label for="register-email">Email</label>
        <input id="register-email" name="email" type="email" autocomplete="email" required>
        <label for="register-password">Password</label>
        <input id="register-password" name="password" type="password" autocomplete="new-password" minlength="4" required>
        <button class="btn btn-success" type="submit">Register</button>
    </form>
</section>
