<?php
$host = 'localhost';
$db = 'jde_db_v5';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);

/**
 * Normalizes and resolves product image paths for both User and Admin panels.
 * Handles missing prefixes, absolute URLs, and default fallbacks.
 * 
 * @param string $path The image path from the database
 * @param string $context 'user' or 'admin' (optional, detects automatically)
 * @return string The resolved path relative to the caller
 */
if (!function_exists('resolveProductPath')) {
    function resolveProductPath($path) {
        // Fallback for empty or default images
        if (empty($path) || $path === 'default.jpg' || $path === 'default.png') {
            $path = 'assets/img/logojd.png';
        }

        // If it's already an absolute URL (with http), return it
        if (preg_match('/^https?:\/\//', $path)) {
            return $path;
        }

        // Clean the path: remove common relative prefixes to get the "clean path" from JDE_USER root
        $cleanPath = str_replace(['../../JDE_USER/', '../'], '', $path);
        
        // Ensure the path is relative to the JDE_USER root
        if (strpos($cleanPath, 'assets/') !== 0 && strpos($cleanPath, 'backend/') !== 0 && strpos($cleanPath, 'uploads/') !== 0) {
            $cleanPath = 'assets/img/products/' . $cleanPath;
        }

        // --- NEW: Stable Base URL Resolution ---
        $self = $_SERVER['PHP_SELF'] ?? '';
        $projectNamePath = '';
        
        // Find where the explicit app folders start
        $pos = strpos($self, '/JDE_USER/');
        if ($pos === false) {
            $pos = strpos($self, '/JDE_ADMIN/');
        }

        if ($pos !== false) {
            $projectNamePath = substr($self, 0, $pos);
        }

        // Return a path that is absolute from the server root
        // Format: /ProjectFolder/JDE_USER/assets/...
        return $projectNamePath . '/JDE_USER/' . $cleanPath;
    }
}