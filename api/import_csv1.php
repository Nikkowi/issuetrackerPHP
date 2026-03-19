<?php
require_once '../config/database.php';

$conn = getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST method required.']);
    exit();
}

if (empty($_FILES['file'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded.']);
    exit();
}

$imported_by = !empty($_POST['importedBy']) ? intval($_POST['importedBy']) : null;
$file = $_FILES['file']['tmp_name'];
$filename = $_FILES['file']['name'];

$handle = fopen($file, 'r');
if (!$handle) {
    echo json_encode(['success' => false, 'message' => 'Could not read file.']);
    exit();
}

// Skip header row
$header = fgetcsv($handle);

$count = 0;
$errors = [];

while (($row = fgetcsv($handle)) !== false) {
    if (count($row) < 4) continue;

    $description     = trim($row[0] ?? '');
    $state           = trim($row[1] ?? 'New');
    $priority        = trim($row[2] ?? 'Medium');
    $dashboard       = trim($row[3] ?? '');
    $module          = trim($row[4] ?? '');
    $date_identified = trim($row[5] ?? date('Y-m-d'));

    if (empty($description)) continue;

    // Validate state
    $validStates = ['New', 'Bug', 'Open', 'In Progress', 'Resolved'];
    if (!in_array($state, $validStates)) $state = 'New';

    // Validate priority
    $validPriorities = ['Low', 'Medium', 'High', 'Critical'];
    if (!in_array($priority, $validPriorities)) $priority = 'Medium';

    $stmt = $conn->prepare("INSERT INTO issues (description, state, priority, dashboard, module, date_identified, source) VALUES (?, ?, ?, ?, ?, ?, 'CSV Import')");
    $stmt->bind_param("ssssss", $description, $state, $priority, $dashboard, $module, $date_identified);

    if ($stmt->execute()) {
        $count++;
    } else {
        $errors[] = "Row failed: $description";
    }
    $stmt->close();
}

fclose($handle);

// Log the import
$logStmt = $conn->prepare("INSERT INTO issue_imports (filename, imported_by, records_count) VALUES (?, ?, ?)");
$logStmt->bind_param("sii", $filename, $imported_by, $count);
$logStmt->execute();
$logStmt->close();

echo json_encode([
    'success' => true,
    'message' => "$count records imported successfully.",
    'data'    => ['count' => $count, 'errors' => $errors]
]);

$conn->close();
?>
