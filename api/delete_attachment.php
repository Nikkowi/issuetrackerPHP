<?php
require_once '../config/database.php';

$conn = getConnection();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID is required.']);
    exit();
}

// Get file path first
$stmt = $conn->prepare("SELECT file_path FROM attachments WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Attachment not found.']);
    exit();
}

// Delete physical file
if (file_exists($row['file_path'])) {
    unlink($row['file_path']);
}

// Delete from database
$delStmt = $conn->prepare("DELETE FROM attachments WHERE id = ?");
$delStmt->bind_param("i", $id);
$delStmt->execute();
$delStmt->close();

echo json_encode(['success' => true, 'message' => 'Attachment deleted successfully.']);

$conn->close();
?>