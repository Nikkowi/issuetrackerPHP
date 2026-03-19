<?php
require_once '../config/database.php';

$conn = getConnection();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID is required.']);
    exit();
}

$sql = "SELECT i.*, 
        u1.name AS issued_by_name, 
        u2.name AS assigned_to_name
        FROM issues i
        LEFT JOIN users u1 ON i.issued_by = u1.id
        LEFT JOIN users u2 ON i.assigned_to = u2.id
        WHERE i.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$issue = $result->fetch_assoc();

if (!$issue) {
    echo json_encode(['success' => false, 'message' => 'Issue not found.']);
    exit();
}

echo json_encode(['success' => true, 'data' => $issue]);

$stmt->close();
$conn->close();
?>