<?php
require_once '../config/database.php';

$conn = getConnection();

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['description'])) {
    echo json_encode(['success' => false, 'message' => 'Description is required.']);
    exit();
}

$dashboard      = $input['dashboard'] ?? null;
$module         = $input['module'] ?? null;
$description    = $input['description'];
$state          = $input['state'] ?? 'New';
$priority       = $input['priority'] ?? 'Medium';
$issued_by      = !empty($input['issuedBy']) ? intval($input['issuedBy']) : null;
$assigned_to    = !empty($input['assignedTo']) ? intval($input['assignedTo']) : null;
$date_identified = $input['dateIdentified'] ?? date('Y-m-d');

$sql = "INSERT INTO issues (dashboard, module, description, state, priority, issued_by, assigned_to, date_identified, source)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Manual')";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssiiis",
    $dashboard,
    $module,
    $description,
    $state,
    $priority,
    $issued_by,
    $assigned_to,
    $date_identified
);

// Fix - use correct param count
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssiis",
    $dashboard,
    $module,
    $description,
    $state,
    $priority,
    $issued_by,
    $assigned_to,
    $date_identified
);

$stmt->execute();
$newId = $conn->insert_id;

echo json_encode(['success' => true, 'data' => ['id' => $newId], 'message' => 'Issue created successfully.']);

$stmt->close();
$conn->close();
?>