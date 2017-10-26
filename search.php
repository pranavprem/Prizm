<?php
#include('util.php');
#$json = getJson();
#echo '<script>var json = '.$json.'</script>';

?>

<html>
<head>
 <script type="text/javascript" src="js/jquery-2.1.1.js"></script>
  <script type="text/javascript" src="js/jquery.autocomplete.min.js"></script>
 
</head>
<body>
<input id="autocomplete"/>

<Script>
$(document).ready(function(){
	var json =  [{
        "Name": "bob barker",
        "image": "./images/bbarker.png"
    }, {
        "Name": "Jill bill",
        "image": "./images/jBill.png"
    }, {
        "Name": "John Doe",
        "image": "./images/jdoe.png"
    }];
	
	 $("input#autocomplete").autocomplete({
        source: json,
        select: function( event, ui ) { 
            window.location.href = ui.item.value;
        }
    });
	
});
</script>



</body>

</html>