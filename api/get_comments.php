<?php
require_once '../config/database.php';

$conn = getConnection();

$issue_id = isset($_GET['issue_id']) ? intval($_GET['issue_id']) : 0;

if (!$issue_id) {
    echo json_encode(['success' => false, 'message' => 'issue_id is required.']);
    exit();
}

$sql = "SELECT c.*, u.name AS user_name 
        FROM comments c
        LEFT JOIN users u ON c.user_id = u.id
        WHERE c.issue_id = ?
        ORDER BY c.created_at ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $issue_id);
$stmt->execute();
$result = $stmt->get_result();

$comments = [];
while ($row = $result->fetch_assoc()) {
    $comments[] = $row;
}

echo json_encode(['success' => true, 'data' => $comments]);

$stmt->close();
$conn->close();
?>