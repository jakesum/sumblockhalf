<?php
$data = file_get_contents('https://apiv2.sumcoinaverage.com/indices/global/ticker/BTCUSD');
$price = json_decode($data, true);
$sumPrice = (float)$price["last"];
$file = "price.txt";
$fh = fopen($file, 'w') or die("can't open file");
fwrite($fh, $sumPrice);
fclose($fh);
?>