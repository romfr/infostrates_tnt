<?php
$now = date('Y-m-d');

$ch = curl_init();
// Configuration de l'URL et d'autres options
curl_setopt($ch, CURLOPT_URL, "https://www.mopapp.com/api/sales/insert.json?account=infostrates&key=f84fd375a6ad4313a13736d665b859d9&version=1.0&application=10668&date=".$now."&country=fr&type=0&quantity=1&currency=eur&revenue=0.00&profit=0.00");
curl_setopt($ch, CURLOPT_HEADER, 0);

// Récupération de l'URL et affichage sur le naviguateur
curl_exec($ch);
curl_close($ch);
?>