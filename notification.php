<?php
// Suppress warnings/notices from polluting JSON output
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/config/db.php';

header('Content-Type: application/json');

if (empty($auth_user) && !empty($_SESSION['auth_user'])) {
    $auth_user = $_SESSION['auth_user'];
}

if (empty($auth_user) && !empty($_SESSION['tabs']) && count($_SESSION['tabs']) === 1) {
    $single_tab = reset($_SESSION['tabs']);
    if (!empty($single_tab['auth_user'])) {
        $auth_user = $single_tab['auth_user'];
    }
}

if (empty($auth_user)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

/** @var array $auth_user */
/** @var PDO $pdo */

$user_id = (int)$auth_user['user_id'];
$action  = $_POST['action'] ?? $_GET['action'] ?? 'fetch';

try {
    if ($action === 'fetch') {
        $stmt = $pdo->prepare('
            SELECT notification_id, title, message, link_url, is_read, created_at 
            FROM notification 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 25
        ');
        $stmt->execute([$user_id]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $unread_count = 0;
        foreach ($notifications as &$n) {
            if (!$n['is_read']) {
                $unread_count++;
            }
            $n['time_ago'] = !empty($n['created_at']) ? date('M d, g:i a', strtotime($n['created_at'])) : '';
        }

        echo json_encode([
            'success'       => true,
            'unread_count'  => $unread_count,
            'notifications' => $notifications
        ]);
        exit;
    }

    if ($action === 'mark_read') {
        $update = $pdo->prepare('UPDATE notification SET is_read = TRUE WHERE user_id = ?');
        $update->execute([$user_id]);

        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Invalid action']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit;
}