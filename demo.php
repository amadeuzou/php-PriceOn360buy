<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>京东商城价格图片识别</title>
</head>
<body >

<form action="" method="post" id="frm"/>
<p>请输入来自京东商城的价格图片地址：</p>
<input name="data" id="data"  type="text" maxlength="1024" size = "60"  value="http://price.360buyimg.com/gp574361,3.png"/> 
<input name="Price" type="submit" value="Price" class="button" id='Price'>


<?php 

require_once("PriceOn360Buy.php");

if (isset($_POST['data'])) {

    $data = $_POST['data']; 

    $time_start = getmicrotime();

    $price360 = new PriceOn360Buy($data);
    echo "<br>Result: ".sprintf("%16.2f", $price360->Price()).'<br>';

    $time_end = getmicrotime();
    $time = $time_end - $time_start;
 
    echo "Run-Time: $time seconds<br>";  

	echo '<br /><br /><img src='.$data.' /><br /><a href="#" id="get">Download Image</a>';

}

function getmicrotime(){ 
    list($usec, $sec) = explode(" ",microtime()); 
    return ((float)$usec + (float)$sec); 
    } 


?>

</body >
</html>