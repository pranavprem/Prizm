<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/util.php';
prizm_session();
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?php echo prizm_h(prizm_csrf_token()); ?>">
<link rel="icon" href="favicon.png" type="image/png">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" href="css/site.css">
<link rel="stylesheet" href="css/products.css">
<link rel="stylesheet" href="css/form-elements.css">
<?php if (!empty($loadAutocomplete)) { ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jquery-ui-dist@1.13.3/jquery-ui.min.css">
<?php } ?>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/js/bootstrap.min.js"></script>
<script src="js/custom.js"></script>
<script src="js/custom-scroll.js"></script>
