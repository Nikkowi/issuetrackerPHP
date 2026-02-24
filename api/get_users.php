<?php
require_once '../config/database.php';

$conn = getConnection();

$role = isset($_GET['role']) ? $_GET['role'] : null;

if ($role) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE role = ? ORDER BY name ASC");
    $stmt->bind_param("s", $role);
} else {
    $stmt = $conn->prepare("SELECT * FROM users ORDER BY name ASC");
}

$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo json_encode(['success' => true, 'data' => $users]);

$stmt->close();
$conn->close();
?>