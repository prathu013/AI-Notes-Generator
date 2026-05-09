<?php
$ch = curl_init('https://generativelanguage.googleapis.com/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_ENCODING, '');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$r = curl_exec($ch);
var_dump(strlen($r));
var_dump(curl_error($ch));
