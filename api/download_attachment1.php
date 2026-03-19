<?php
require_once '../config/database.php';

$conn = getConnection();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID is required.']);
    exit();
}

$stmt = $conn->prepare("SELECT * FROM attachments WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$row || !file_exists($row['file_path'])) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'File not found.']);
    exit();
}

header('Content-Type: ' . $row['file_type']);
header('Content-Disposition: attachment; filename="' . $row['original_name'] . '"');
header('Content-Length: ' . $row['file_size']);
readfile($row['file_path']);
exit();
?>
