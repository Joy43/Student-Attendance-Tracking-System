<?php
/**
 * Notifications API
 * GET  ?action=fetch            → return unread (+ recent read) notifications for logged-in faculty
 * POST ?action=mark_read&id=N  → mark a single notification as read
 * POST ?action=mark_all_read   → mark all notifications as read for this faculty
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['faculty'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once dirname(__DIR__) . "/src/database.php";

$faculty_user = mysqli_real_escape_string($conn, $_SESSION['faculty']);
$action       = $_GET['action'] ?? $_POST['action'] ?? 'fetch';

switch ($action) {

    // ─── Fetch latest 30 notifications ───────────────────────────
    case 'fetch':
        $q = mysqli_query($conn, "
            SELECT id, type, title, message, is_read, created_at
            FROM notifications
            WHERE faculty_user = '$faculty_user'
            ORDER BY created_at DESC
            LIMIT 30
        ");
        $rows = [];
        $unread = 0;
        if ($q) {
            while ($r = mysqli_fetch_assoc($q)) {
                $rows[] = $r;
                if ($r['is_read'] == 0) $unread++;
            }
        }
        echo json_encode(['notifications' => $rows, 'unread' => $unread]);
        break;

    // ─── Mark single notification as read ────────────────────────
    case 'mark_read':
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id > 0) {
            mysqli_query($conn, "UPDATE notifications SET is_read=1 WHERE id='$id' AND faculty_user='$faculty_user'");
        }
        echo json_encode(['success' => true]);
        break;

    // ─── Mark all as read ────────────────────────────────────────
    case 'mark_all_read':
        mysqli_query($conn, "UPDATE notifications SET is_read=1 WHERE faculty_user='$faculty_user'");
        echo json_encode(['success' => true]);
        break;

    // ─── Delete a notification ────────────────────────────────────
    case 'delete':
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id > 0) {
            mysqli_query($conn, "DELETE FROM notifications WHERE id='$id' AND faculty_user='$faculty_user'");
        }
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action']);
}

mysqli_close($conn);
