<?php
$key = 'AQ.Ab8RN6IwiumbDiy5KdigfYprPTckcKi-IWk_fltiNAEUozgvaQ';
$ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models?key=' . $key);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$res = curl_exec($ch);
curl_close($ch);
$data = json_decode($res, true);
if (isset($data['models'])) {
    foreach ($data['models'] as $m) {
        if (strpos($m['name'], 'flash') !== false) {
            echo $m['name'] . "\n";
        }
    }
} else {
    echo $res;
}
