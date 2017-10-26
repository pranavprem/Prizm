<?php
session_start();
?>

<!doctype html>
<html>

    <head>
        <?php require "includes/head.php" ?>
        <title>Prizm</title>
        <?php ob_start(); ?>
    </head>
    <body>
        <script type="text/javascript" src="js/custom-scroll.js"></script>
        <?php require "includes/newuser.php" ?>
        <?php require "includes/home.php" ?>
        <?php require "includes/navbar.php" ?>
        <?php require "includes/about.php" ?>
        <?php require "includes/news.php" ?>
        <?php require "includes/contacts.php" ?>
        <?php require "includes/login.php" ?>
        <footer class="footer">
            <div class="container">
                <div class="credits">
                    This website is based on styles and animations from <a href="https://bootstrapmade.com/free-business-bootstrap-themes-website-templates/">Business Bootstrap Themes</a> by <a href="https://bootstrapmade.com/">BootstrapMade</a>
                </div>
            </div>
        </footer>
    </body>
</html>