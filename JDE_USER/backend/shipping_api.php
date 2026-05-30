<?php
/**
 * shipping_api.php — Handles dynamic shipping fee calculation based on distance.
 * Implements OpenStreetMap (Nominatim) and OSRM APIs with local caching for performance.
 */
session_start();
header('Content-Type: application/json');

// Configuration
$BASE_FEE = 50.00; // Base rate (starting fee)
$RATE_PER_KM = 2.00; // Additional rate per kilometer

// Cache configuration
$CACHE_DIR = __DIR__ . '/cache/shipping/';
if (!is_dir($CACHE_DIR)) {
    mkdir($CACHE_DIR, 0777, true);
}
$CACHE_TIME = 60 * 60 * 24 * 30; // 30 days cache for static distance data

// Store location - 11 Esperanza, Hilltop, Novaliches, Quezon City, Metro Manila
$store_lat = 14.7352;  // Approximate latitude for Novaliches
$store_lon = 121.0458; // Approximate longitude for Novaliches

// Function to get coordinates from address via Nominatim
function getCoordinates($address, $cacheDir, $cacheTime) {
    $cacheKey = 'coord_' . md5($address) . '.json';
    $cacheFile = $cacheDir . $cacheKey;

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
        return json_decode(file_get_contents($cacheFile), true);
    }

    $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($address) . "&limit=1";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'JDE_App/1.0 (contact@customtailor.com)'); // Nominatim requires User-Agent
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $data = json_decode($response, true);
        if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
            $coords = [
                'lat' => $data[0]['lat'],
                'lon' => $data[0]['lon']
            ];
            file_put_contents($cacheFile, json_encode($coords));
            return $coords;
        }
    }
    return null;
}

// Function to get distance from OSRM
function getDistanceOSRM($lon1, $lat1, $lon2, $lat2, $cacheDir, $cacheTime) {
    $cacheKey = 'dist_' . md5("$lon1,$lat1,$lon2,$lat2") . '.json';
    $cacheFile = $cacheDir . $cacheKey;

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
        return json_decode(file_get_contents($cacheFile), true);
    }

    // Validates coordinates to be roughly within the Philippines
    if ($lat1 < 4 || $lat1 > 22 || $lon1 < 116 || $lon1 > 127) return null;
    if ($lat2 < 4 || $lat2 > 22 || $lon2 < 116 || $lon2 > 127) return null;

    $url = "http://router.project-osrm.org/route/v1/driving/{$lon1},{$lat1};{$lon2},{$lat2}?overview=false";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'JDE_App/1.0');
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['routes'][0]['distance'])) {
            $distance = $data['routes'][0]['distance'] / 1000; // Return distance in kilometers
            file_put_contents($cacheFile, json_encode($distance));
            return $distance;
        }
    }
    return null;
}

// Main logic
$province = $_GET['province'] ?? '';
$city = $_GET['city'] ?? '';
$barangay = $_GET['barangay'] ?? '';
$street = $_GET['address'] ?? '';

if (empty($province) || empty($city)) {
    echo json_encode(['success' => false, 'message' => 'Missing location details']);
    exit;
}

$destination = trim("$street, $barangay, $city, $province");
$destination_fallback = trim("$city, $province"); // Fallback if exact matching fails

$distance = null;
$method = "OSRM/Nominatim";

// Get customer coordinates
$customer_coords = getCoordinates($destination, $CACHE_DIR, $CACHE_TIME);

// Retry with less specific address if first try failed
if (!$customer_coords && (!empty($city) || !empty($province))) {
    $customer_coords = getCoordinates($destination_fallback, $CACHE_DIR, $CACHE_TIME);
}

if ($customer_coords) {
    if (file_exists($CACHE_DIR . 'coord_' . md5($destination_fallback) . '.json') && !$customer_coords) {
         // This block is just a safeguard, the function handles cache
    }
    $distance = getDistanceOSRM($store_lon, $store_lat, $customer_coords['lon'], $customer_coords['lat'], $CACHE_DIR, $CACHE_TIME);
}

// Fallback if API fails
if ($distance === null) {
    $method = "Fallback Mocks";
    
    $city_lower = strtolower($city);
    $province_lower = strtolower($province);
    
    if (strpos($city_lower, 'quezon city') !== false) {
        $distance = 5.0;
    } elseif (strpos($city_lower, 'caloocan') !== false) {
        $distance = 7.0;
    } elseif (strpos($city_lower, 'manila') !== false) {
        $distance = 15.0;
    } elseif (strpos($province_lower, 'bulacan') !== false) {
        $distance = 25.0;
    } elseif (strpos($province_lower, 'cavite') !== false || strpos($province_lower, 'laguna') !== false || strpos($province_lower, 'rizal') !== false) {
        $distance = 45.0;
    } else {
        $distance = 100.0; // very far logic
    }
}

// Calculate Fee: Starting with the Base Fee and then adding the distance * rate
$shippingFee = $BASE_FEE + ($distance * $RATE_PER_KM);

// Check for free shipping eligibility based on cart subtotal
$subtotal = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        if (isset($item['selected']) && !$item['selected']) continue;
        $subtotal += (float)$item['price'] * (int)$item['quantity'];
    }
}

$originalShippingFee = $shippingFee;
$isFreeShipping = false;

if ($subtotal >= 1500) {
    $shippingFee = 0;
    $method = "Free Shipping (Order ≥ ₱1500)";
    $isFreeShipping = true;
}

// Ceiling the fee to avoid decimal places in UI, typical for shipping
$shippingFee = ceil($shippingFee);
$originalShippingFee = ceil($originalShippingFee);

// Save to session for backend verification during order placement
$_SESSION['calculated_shipping_fee'] = $shippingFee;
$_SESSION['original_shipping_fee'] = $originalShippingFee;

echo json_encode([
    'success' => true,
    'distance_km' => round($distance, 2),
    'shipping_fee' => (float)$shippingFee,
    'original_shipping_fee' => (float)$originalShippingFee,
    'is_free_shipping' => $isFreeShipping,
    'method' => $method,
    'destination' => $destination,
    'cached' => true // Optimization confirmation
]);
