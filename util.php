<?php

function createProductUI($id,$img,$desc,$title,$price)
{
	$displayId = "'".$id."'";
echo ' <div class="col-sm-3">
            <div class="thumbnail">
                <img class="group list-group-image" src="'.$img.'"  alt="" />
				<div class="caption">
				<div class="row">
                 <a href="displayproduct.php?pid='.$id.'">'.$title.'</a>
				 </div>
				 <br/>
                    <div class="row price">
                                $ '.$price.'.00
					</div>
					<div class="row cart" onclick="addToCart('.$displayId.')">
                        
                            Add to cart
                    </div>
                </div>
            </div>
        </div>
       
';

}


function createProductThumbnailUI($id,$img,$desc,$title,$price)
{
	$displayId = "'".$id."'";
echo ' <div class="col-sm-3">
            <div class="thumbnail small-img">
                <img class="group list-group-image2" src="'.$img.'" alt="" />
                <div class="caption">
                    <h4 class="group inner list-group-item-heading">
                        <a href="displayproduct.php?pid='.$id.'">'.$title.'</a></h4>
                    <div class="row">
                        <div class="col-xs-12 col-md-6">
                            <p class="lead">
                                $ '.$price.'</p>
                        </div>
                        <div class="col-xs-12 col-md-6">
                            <a class="btn btn-success" href="#" onclick="addToCart('.$displayId.')">Add to cart</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
       
';

}

function createCartUI($id,$img,$title,$price,$desc)
{
	$displayId = "'".$id."'";
echo ' <tr>
					<td data-th="Product">
								<div class="row">
									<div class="col-sm-2 hidden-xs"><img src="'.$img.'" alt="..." class="img-responsive"/></div>
									<div class="col-sm-10">
										<a href="displayproduct.php?pid='.$id.'"><h4 class="nomargin">'.$title.'</h4></a>
										<p>'.$desc.'</p>
									</div>
								</div>
							</td>
							
							<td data-th="Price">$'.$price.'</td>
							
						</tr>
       
';
	
}

function createCartTotalUi($total)
{
echo '<tfoot>
						<tr>
							<td><a href="allproducts.php" class="btn btn-warning"><i class="fa fa-angle-left"></i> Continue Shopping</a></td>
							<td colspan="2" class="hidden-xs"></td>
							<td class="hidden-xs text-center"><strong>Total $'.$total.'</strong></td>
							<td><a href="#" class="btn btn-success btn-block">Checkout <i class="fa fa-angle-right"></i></a></td>
						</tr>
					</tfoot>
';
	
}

function buildProductsUI($isSingleProduct, $productId)
{
include('config.php');
$content = "";

if($isSingleProduct)
	$sql = "select * from products where id = '$productId'";
else
	$sql = "select * from products";

$result = mysql_query($sql);

while ($row = mysql_fetch_array($result)) {
	$id = $row['id'];
	$img = $row['imgUrl'];
	$desc = $row['description'];
	$title = $row['name'];
	$price = $row['price'];
		$content = $content.createProductUI($id,$img,$desc,$title,$price); 
}

echo $content;

}

function buildCartUI($productIds)
{
include('config.php');
$content = "";
$sql = "select * from products where id in (".$productIds.")";

$result = mysql_query($sql);
$total = 0;
while ($row = mysql_fetch_array($result)) {
	$id = $row['id'];
	$img = $row['imgUrl'];
	$desc = $row['description'];
	$title = $row['name'];
	$price = $row['price'];
	$content = $content.createCartUI($id,$img,$title,$price,$desc); 
	$total = $total + $price;
}

echo $content;
return $total;

}

function buildAllProducts()
{
$urls = array("https://localhost/valarshopoholics/exposeproducts.php", "https://localhost/valarshopoholics/exposeproducts.php", "https://localhost/valarshopoholics/exposeproducts.php");

$length = count($urls);

for($i=0;$i<$length;$i++)
{
$content = "";
$url = $urls[$i];
$ch=curl_init();
curl_setopt($ch,CURLOPT_URL,$url);
curl_setopt($ch,CURLOPT_HEADER,0);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
$contents = curl_exec($ch);
curl_close($ch);
$rows = json_decode($contents, true);

foreach($rows as $row)
{
	$content = $content.createProductUI($row['id'],$row['imgUrl'],$row['description'],$row['name'],$row['price']); 
}

}

}

function updateHits($productId)
{
include('config.php');
$sql = "update products set hits=hits+1 where id='".$productId."'";
$result = mysql_query($sql);

}

function buildRatingStars($rating,$isMultipleRatings,$count)
{
					if($isMultipleRatings)
								$name = "ratings".$count;
							else
								$name = "ratings";
			
	
	for($i=5;$i>=1;$i--)
			{
							
							if($rating == $i)
							{
							echo '
								<input type="radio" id="star'.$i.'" name="'.$name.'" value="'.$i.'" checked/><label class = "full" for="star'.$i.'"></label>
								';
							}
							else
							{
								echo '
								<input type="radio" id="star'.$i.'" name="'.$name.'" value="'.$i.'" /><label class = "full" for="star'.$i.'"></label>
								';
							}
			}
}

function getJson()
{
include('config.php');

$sql = "SELECT id as value,name as label FROM products";
$result = mysql_query($sql);

$rows = array();
while ($row = mysql_fetch_assoc($result)) {
	$row['value'] = "displayproducts.php?pid=".$row['value'];
    $rows[] = $row;
}
header("Content-type:application/json;charset=utf-8");
$json =  json_encode($rows);

return $json;

}

?>