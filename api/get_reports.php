<?php
require_once '../config/database.php';

$conn = getConnection();

$sql = "SELECT r.*, u.name AS generated_by_name 
        FROM reports r
        LEFT JOIN users u ON r.generated_by = u.id
        ORDER BY r.generated_at DESC";

$result = $conn->query($sql);

$reports = [];
while ($row = $result->fetch_assoc()) {
    $reports[] = $row;
}

echo json_encode(['success' => true, 'data' => $reports]);

$conn->close();
?>