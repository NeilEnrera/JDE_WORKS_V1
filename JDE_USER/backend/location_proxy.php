<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$endpoint = $_GET['endpoint'] ?? '';

if (empty($endpoint)) {
    echo json_encode(['error' => 'No endpoint specified']);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9\/\-]+$/', $endpoint)) {
    echo json_encode(['error' => 'Invalid endpoint format']);
    exit;
}

// --- CACHING LOGIC ---
$cacheDir = __DIR__ . '/cache/locations/';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0777, true);
}

// Create a safe filename from the endpoint
$cacheKey = str_replace('/', '_', trim($endpoint, '/')) . '.json';
$cacheFile = $cacheDir . $cacheKey;
$cacheTime = 60 * 60 * 24 * 7; // Cache for 1 week (geographical data is static)

if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
    // Return cached data
    echo file_get_contents($cacheFile);
    exit;
}
// --------------------

$url = "https://psgc.gitlab.io/api/" . ltrim($endpoint, '/');

// Use cURL for better compatibility and error handling
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'PHP-Proxy/1.0');
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // In case of local dev SSL issues

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($response === false) {
    echo json_encode(['error' => 'CURL Error: ' . $error]);
} elseif ($httpCode !== 200) {
    echo json_encode(['error' => "API returned HTTP $httpCode", "url" => $url]);
} else {
    // Save to cache before returning
    if (!empty($response)) {
        file_put_contents($cacheFile, $response);
    }
    echo $response;
}
