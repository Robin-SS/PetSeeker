<?php
/**
 * Supabase Storage REST API Helper.
 */

// Load environment configuration
require_once dirname(__DIR__) . '/config/env.php';

if (!defined('SUPABASE_URL')) {
    define('SUPABASE_URL', rtrim(getenv('SUPABASE_URL') ?: '', '/'));
}

if (!defined('SUPABASE_SERVICE_KEY')) {
    define('SUPABASE_SERVICE_KEY', getenv('SUPABASE_SERVICE_ROLE_KEY') ?: (getenv('SUPABASE_SERVICE_KEY') ?: ''));
}

if (!defined('SUPABASE_BUCKET')) {
    define('SUPABASE_BUCKET', getenv('SUPABASE_STORAGE_BUCKET') ?: 'pet-images');
}

/**
 * Uploads a file array ($_FILES['input_name']) directly to Supabase Storage.
 *
 * @param array $file $_FILES['pet_photo'] or similar entry
 * @return string|false Public image URL on success, false on failure
 */
function upload_pet_image(array $file): string|false {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        return false;
    }

    // Validate MIME type
    $allowed_mimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif'
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!array_key_exists($mime_type, $allowed_mimes)) {
        return false;
    }

    // Generate unique storage filename
    $extension = $allowed_mimes[$mime_type];
    $filename = 'pet_' . bin2hex(random_bytes(10)) . '_' . time() . '.' . $extension;

    $endpoint = rtrim(SUPABASE_URL, '/') . '/storage/v1/object/' . SUPABASE_BUCKET . '/' . $filename;
    $file_content = file_get_contents($file['tmp_name']);

    if ($file_content === false) {
        return false;
    }

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $file_content,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . SUPABASE_SERVICE_KEY,
            'apikey: ' . SUPABASE_SERVICE_KEY,
            'Content-Type: ' . $mime_type,
            'x-upsert: true'
        ],
        CURLOPT_TIMEOUT        => 30
    ]);

    $response    = curl_exec($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status_code >= 200 && $status_code < 300) {
        // Return full public URL for direct database storage and browser rendering
        return rtrim(SUPABASE_URL, '/') . '/storage/v1/object/public/' . SUPABASE_BUCKET . '/' . $filename;
    }

    return false;
}

/**
 * Deletes an image from the Supabase bucket given its stored URL or path.
 *
 * @param string|null $image_url Full Supabase public URL
 * @return bool True if deleted or empty, false on API failure
 */
function delete_pet_image(?string $image_url): bool {
    if (empty($image_url)) {
        return true;
    }

    // Extract filename from URL
    $path = parse_url($image_url, PHP_URL_PATH);
    if (!$path) {
        return false;
    }

    $segments = explode('/', trim($path, '/'));
    $filename = end($segments);

    if (empty($filename)) {
        return false;
    }

    $endpoint = rtrim(SUPABASE_URL, '/') . '/storage/v1/object/' . SUPABASE_BUCKET;
    $payload  = json_encode(['prefixes' => [$filename]]);

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => 'DELETE',
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . SUPABASE_SERVICE_KEY,
            'apikey: ' . SUPABASE_SERVICE_KEY,
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT        => 30
    ]);

    $response    = curl_exec($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($status_code >= 200 && $status_code < 300);
}

/**
 * Resolves a pet photo path or URL for HTML <img> rendering.
 * Safely handles Supabase full URLs, legacy relative paths, and missing images.
 *
 * @param string|null $photo
 * @return string|null Web-accessible URL or null
 */
function get_pet_photo_url(?string $photo): ?string {
    if (empty($photo)) {
        return null;
    }

    // Return direct URL if already hosted on Supabase or an external CDN
    if (str_starts_with($photo, 'http://') || str_starts_with($photo, 'https://')) {
        return $photo;
    }

    // Handle legacy local uploads if the file exists on the server
    if (file_exists($photo)) {
        return (defined('BASE_URL') ? BASE_URL : '') . ltrim($photo, '/');
    }

    return null;
}