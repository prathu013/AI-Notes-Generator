<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_ORIGIN'] = 'http://localhost';
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
$_SERVER['CONTENT_TYPE'] = 'application/json';
$json = json_encode(['email' => 'piyush12@gmail.com', 'password' => 'password123']);
$stream = fopen('php://memory', 'r+');
fwrite($stream, $json);
rewind($stream);
// Mock Validator::json() if needed, or better, we can't easily mock php://input
// wait, Validator::json() reads php://input.
// So let's just make a curl call from a php script.
$ch = curl_init('http://localhost/AI-Notes-Generator/api/auth/login.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Requested-With: XMLHttpRequest'
]);
$response = curl_exec($ch);
echo "RESPONSE:\n";
echo $response;
echo "\nHTTP CODE:\n";
echo curl_getinfo($ch, CURLINFO_HTTP_CODE);
if(curl_errno($ch)){
    echo "\nCURL ERROR: " . curl_error($ch);
}
curl_close($ch);
