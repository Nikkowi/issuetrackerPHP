<?php
require_once '../config/database.php';

$conn = getConnection();

$issue_id = isset($_GET['issue_id']) ? intval($_GET['issue_id']) : 0;

if (!$issue_id) {
    echo json_encode(['success' => false, 'message' => 'issue_id is required.']);
    exit();
}

$stmt = $conn->prepare("SELECT * FROM attachments WHERE issue_id = ? ORDER BY uploaded_at DESC");
$stmt->bind_param("i", $issue_id);
$stmt->execute();
$result = $stmt->get_result();

$attachments = [];
while ($row = $result->fetch_assoc()) {
    $attachments[] = $row;
}

echo json_encode(['success' => true, 'data' => $attachments]);

$stmt->close();
$conn->close();
?>