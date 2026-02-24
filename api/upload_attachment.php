<?php
require_once '../config/database.php';

$conn = getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST method required.']);
    exit();
}

$issue_id    = !empty($_POST['issue_id']) ? intval($_POST['issue_id']) : 0;
$uploaded_by = !empty($_POST['uploaded_by']) ? intval($_POST['uploaded_by']) : null;

if (!$issue_id) {
    echo json_encode(['success' => false, 'message' => 'issue_id is required.']);
    exit();
}

// Check max 5 attachments
$countStmt = $conn->prepare("SELECT COUNT(*) as count FROM attachments WHERE issue_id = ?");
$countStmt->bind_param("i", $issue_id);
$countStmt->execute();
$count = $countStmt->get_result()->fetch_assoc()['count'];
$countStmt->close();

if ($count >= 5) {
    echo json_encode(['success' => false, 'message' => 'Maximum of 5 attachments per issue reached.']);
    exit();
}

if (empty($_FILES['file'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded.']);
    exit();
}

$file          = $_FILES['file'];
$original_name = $file['name'];
$file_size     = $file['size'];
$file_type     = $file['type'];
$ext           = pathinfo($original_name, PATHINFO_EXTENSION);
$unique_name   = uniqid() . '.' . $ext;
$upload_path   = '../uploads/' . $unique_name;

if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file.']);
    exit();
}

$stmt = $conn->prepare("INSERT INTO attachments (issue_id, filename, original_name, file_path, file_size, file_type, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("isssssi", $issue_id, $unique_name, $original_name, $upload_path, $file_size, $file_type, $uploaded_by);
$stmt->execute();

$newId = $conn->insert_id;

echo json_encode([
    'success' => true,
    'message' => 'File uploaded successfully.',
    'data'    => [
        'id'            => $newId,
        'original_name' => $original_name,
        'filename'      => $unique_name,
        'file_size'     => $file_size,
        'file_type'     => $file_type
    ]
]);

$stmt->close();
$conn->close();
?>