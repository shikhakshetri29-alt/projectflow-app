<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /php-pmapp/dashboard/index.php');
    exit;
}

$taskId   = (int)($_POST['task_id'] ?? 0);
$status   = $_POST['status'] ?? '';
$redirect = $_POST['redirect'] ?? '/tasks/index.php';
$userId   = $_SESSION['user_id'];

if (!$taskId || !in_array($status, ['pending', 'completed'])) {
    header('Location: ' . $redirect);
    exit;
}

// Verify the user has permission to update this task
if (isAdmin()) {
    $stmt = $conn->prepare("UPDATE tasks SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $status, $taskId);
} else {
    // Members can only update their own assigned tasks
    $stmt = $conn->prepare("UPDATE tasks SET status = ? WHERE id = ? AND assigned_to = ?");
    $stmt->bind_param('sii', $status, $taskId, $userId);
}

$stmt->execute();
$stmt->close();

// Redirect back
$sep = str_contains($redirect, '?') ? '&' : '?';
header('Location: ' . $redirect . $sep . 'msg=Task+status+updated');
exit;
