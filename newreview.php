<?php
session_start();
if(isset($_SESSION['uid']))
{
include('config.php');
$pid =  $_GET['pid'];
$uid =  $_GET['uid'];
$stars = $_GET['stars'];
$review = $_GET['review'];

$sql = "INSERT INTO ratings(productid, stars, timestamp, userId, review) VALUES ('$pid',$stars,now(),$uid,'$review') ";
$result = mysql_query($sql);

echo "New review added ";
}

else
{
echo "Please log in to add reviews";	
}

?>