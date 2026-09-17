<?php
require_once __DIR__ . '/includes/supabase_storage.php';

// Create a 1x1 test PNG in memory
$dummy_png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
$test_filename = 'diagnostic_' . time() . '.png';

$endpoint = rtrim(SUPABASE_URL, '/') . '/storage/v1/object/' . SUPABASE_BUCKET . '/' . $test_filename;

echo "<h3>Testing Supabase Storage Connection</h3>";
echo "<strong>Target Endpoint:</strong> " . htmlspecialchars($endpoint) . "<br><br>";

$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $dummy_png,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . SUPABASE_SERVICE_KEY,
        'apikey: ' . SUPABASE_SERVICE_KEY,
        'Content-Type: image/png',
        'x-upsert: true'
    ],
    CURLOPT_TIMEOUT        => 15
]);

$response  = curl_exec($ch);
$curl_err  = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<strong>HTTP Status:</strong> " . $http_code . "<br>";
if ($curl_err) {
    echo "<strong>cURL Error:</strong> " . htmlspecialchars($curl_err) . "<br>";
}
echo "<strong>Raw Response Body:</strong> <pre>" . htmlspecialchars($response ?: 'No response') . "</pre>";