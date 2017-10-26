<?php
session_start();
include('util.php');

function FillDetails()
{

buildProductsUI(false, 0);
}


?>



<html>
<head>
  <!-- Theme Made By www.w3schools.com - No Copyright -->
  <title>Every One Must Shop - Valar Shopoholics</title>
  <meta charset="utf-8">
  <?php require "includes/head.php" ?>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <link href="//maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/form-elements.css">
  <link rel="stylesheet" href="css/products.css">
  <link href="https://fonts.googleapis.com/css?family=Montserrat" rel="stylesheet" type="text/css">
  <link rel='shortcut icon' href='favicon.ico' type='image/x-icon'/ > 
  <link href="https://fonts.googleapis.com/css?family=Lato" rel="stylesheet" type="text/css">
  <link rel="stylesheet" type="text/css" href="engine1/style.css" />
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
  <script src="js/custom.js"></script>
  <link rel="stylesheet" href="path/to/easy-autocomplete.min.css"> 
</head>
<body id="myPage" data-spy="scroll" data-target=".navbar" data-offset="60">

<?php require "includes/othernavbar.php" ?>

<br/><br/>
        
<div class="container">
    <div id="products" class="row list-group">
        <?php FillDetails(); ?>
    </div>
</div>


</body>
</html>
