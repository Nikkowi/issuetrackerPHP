<?php
require_once '../config/database.php';

$conn = getConnection();

$where = "WHERE 1=1";
$params = [];
$types = "";

if (!empty($_GET['state'])) {
    $where .= " AND i.state = ?";
    $params[] = $_GET['state'];
    $types .= "s";
}

if (!empty($_GET['priority'])) {
    $where .= " AND i.priority = ?";
    $params[] = $_GET['priority'];
    $types .= "s";
}

if (!empty($_GET['search'])) {
    $where .= " AND (i.description LIKE ? OR i.dashboard LIKE ? OR i.module LIKE ?)";
    $search = "%" . $_GET['search'] . "%";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $types .= "sss";
}

$sql = "SELECT i.*, 
        u1.name AS issued_by_name, 
        u2.name AS assigned_to_name
        FROM issues i
        LEFT JOIN users u1 ON i.issued_by = u1.id
        LEFT JOIN users u2 ON i.assigned_to = u2.id
        $where
        ORDER BY i.created_at DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$issues = [];
while ($row = $result->fetch_assoc()) {
    $issues[] = $row;
}

echo json_encode(['success' => true, 'data' => $issues]);

$stmt->close();
$conn->close();
?>