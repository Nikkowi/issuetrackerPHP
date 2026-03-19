<?php
require_once '../config/database.php';

$conn = getConnection();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID is required.']);
    exit();
}

$stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Comment deleted.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Comment not found.']);
}

$stmt->close();
$conn->close();
?>