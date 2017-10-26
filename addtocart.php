<?php

if(isset($_GET['pid']))
{

$isSession = session_start();

	if(isset($_SESSION['uid']))
	{
		if($isSession == True){
			if(!isset($_SESSION['product_ids'])){
				// $_SESSION['product_ids'] = $product_id;
				$_SESSION['product_ids'] = "'".$_GET['pid']."'";
			}
			else{
				$_SESSION['product_ids'] = $_SESSION['product_ids'] . "," ."'".$_GET['pid']."'";
		
			}
			echo "Product added to cart";
			
		}
		else{
			echo "Session failed to start";
		}
	}
	else{
		echo "Please log in";
	}
}
?>