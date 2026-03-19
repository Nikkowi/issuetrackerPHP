<?php
require_once '../config/database.php';

$conn = getConnection();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID is required.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

$setClauses = [];
$params = [];
$types = "";

if (isset($input['state'])) {
    $setClauses[] = "state = ?";
    $params[] = $input['state'];
    $types .= "s";
}
if (isset($input['priority'])) {
    $setClauses[] = "priority = ?";
    $params[] = $input['priority'];
    $types .= "s";
}
if (isset($input['dashboard'])) {
    $setClauses[] = "dashboard = ?";
    $params[] = $input['dashboard'];
    $types .= "s";
}
if (isset($input['module'])) {
    $setClauses[] = "module = ?";
    $params[] = $input['module'];
    $types .= "s";
}
if (isset($input['description'])) {
    $setClauses[] = "description = ?";
    $params[] = $input['description'];
    $types .= "s";
}
if (isset($input['assignedTo'])) {
    $setClauses[] = "assigned_to = ?";
    $params[] = intval($input['assignedTo']);
    $types .= "i";
}

if (empty($setClauses)) {
    echo json_encode(['success' => false, 'message' => 'Nothing to update.']);
    exit();
}

$params[] = $id;
$types .= "i";

$sql = "UPDATE issues SET " . implode(", ", $setClauses) . " WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();

echo json_encode(['success' => true, 'message' => 'Issue updated successfully.']);

$stmt->close();
$conn->close();
?>