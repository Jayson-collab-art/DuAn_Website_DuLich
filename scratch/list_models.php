<?php
$apiKey = 'AIzaSyAzp8-jkcHhl7AI_bJYUei14xIpsj2g7ns';
$url = "https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$res = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP: $httpCode\n";
$data = json_decode($res, true);
if (isset($data['models'])) {
    foreach ($data['models'] as $m) {
        if (in_array('generateContent', $m['supportedGenerationMethods'] ?? [])) {
            echo "Model: " . $m['name'] . "\n";
        }
    }
} else {
    echo $res;
}
