<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/supabase_storage.php';

// Authentication guard before any HTML is rendered
if (empty($auth_user)) {
    header('Location: ' . auth_url('login.php'));
    exit;
}

/** @var array $auth_user */
/** @var PDO $pdo */

$user_id = (int)$auth_user['user_id'];
$error   = '';

// Initialize form variables for the view
$name             = '';
$category_id      = 0;
$color            = '';
$location_id      = 0;
$date_found       = date('Y-m-d');
$additional_notes = '';

// Fetch reference data for dropdowns
$categories = $pdo->query('SELECT category_id, species, breed FROM category ORDER BY species, breed')->fetchAll();
$locations  = $pdo->query('SELECT location_id, location_name FROM location ORDER BY location_name')->fetchAll();

// ==========================================
// FORM SUBMISSION HANDLER
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name             = trim($_POST['name'] ?? '');
    $category_id      = (int)($_POST['category_id'] ?? 0);
    $color            = trim($_POST['color'] ?? '');
    $location_id      = (int)($_POST['location_id'] ?? 0);
    $date_found       = trim($_POST['date_found'] ?? '');
    $additional_notes = trim($_POST['additional_notes'] ?? '');

    // Validation (Name is optional for found pets)
    if ($category_id <= 0 || empty($color) || $location_id <= 0 || empty($date_found)) {
        $error = 'Please fill in all required fields marked with an asterisk (*).';
    } else {
        $photo_path = null;

        // Image upload handling via Supabase Storage
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $file_size = $_FILES['photo']['size'];

            if ($file_size > 5 * 1024 * 1024) {
                $error = 'The uploaded photo must be smaller than 5MB.';
            } else {
                $uploaded_url = upload_pet_image($_FILES['photo']);

                if (!$uploaded_url) {
                    $error = 'Failed to upload photo to Supabase storage. Please verify the file is a valid JPG, PNG, or WEBP image.';
                } else {
                    $photo_path = $uploaded_url;
                }
            }
        }

        // Database Insertion Transaction
        if (empty($error)) {
            try {
                $pdo->beginTransaction();

                // 1. Insert Pet Record (stores public Supabase URL or null)
                $pet_stmt = $pdo->prepare('
                    INSERT INTO pet (category_id, name, color, photo) 
                    VALUES (?, ?, ?, ?) 
                    RETURNING pet_id
                ');
                $pet_stmt->execute([$category_id, !empty($name) ? $name : null, $color, $photo_path]);
                $pet_id = (int)$pet_stmt->fetchColumn();

                // 2. Insert Found Pet Listing (Status defaults to \'Approved\' for immediate visibility)
                $found_stmt = $pdo->prepare("
                    INSERT INTO found_pet (pet_id, user_id, location_id, date_found, additional_notes, status) 
                    VALUES (?, ?, ?, ?, ?, 'Approved')
                ");
                $found_stmt->execute([$pet_id, $user_id, $location_id, $date_found, $additional_notes]);

                $pdo->commit();

                // Clean redirect executes safely before HTML headers are sent
                header('Location: ' . auth_url('my_reports.php?tab=found'));
                exit;
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                // Clean up the uploaded Supabase image if DB insertion failed
                if ($photo_path) {
                    delete_pet_image($photo_path);
                }

                $error = 'Database error while saving report: ' . $e->getMessage();
            }
        }
    }
}

// Include header now that all headers/redirects are handled
require_once __DIR__ . '/includes/header.php';

// Render View
include __DIR__ . '/views/reports/form_found.php';

require_once __DIR__ . '/includes/footer.php';