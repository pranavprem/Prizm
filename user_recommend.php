<?php require "./util.php"?>
<?php
$uid = $_GET["uid"];

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "272project";

$con = mysql_connect($servername,$username,$password);
if (!$con)
  {
  die('Could not connect: ' . mysql_error());
  }

mysql_select_db($dbname,$con);
$product_ids = array();
$inner_sql = "SELECT orderid FROM orders WHERE userid = ".$uid."";
$sql = "SELECT distinct productid FROM ordermap WHERE orderid IN ( ".$inner_sql.");";
$result = mysql_query($sql);
while($row = mysql_fetch_array($result)){
    array_push($product_ids,$row["productid"]);
}    

    


foreach ($product_ids as $product_id){
    buildProductsUI(true, $product_id);
}
?>

