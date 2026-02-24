<?php
require_once '../config/database.php';

$conn = getConnection();

$input = json_decode(file_get_contents('php://input'), true);

$generated_by  = !empty($input['generatedBy']) ? intval($input['generatedBy']) : null;
$date_from     = $input['dateFrom'] ?? null;
$date_to       = $input['dateTo'] ?? null;
$status_filter = $input['statusFilter'] ?? 'All';

$where = "WHERE 1=1";
$params = [];
$types = "";

if ($date_from && $date_to) {
    $where .= " AND date_identified BETWEEN ? AND ?";
    $params[] = $date_from;
    $params[] = $date_to;
    $types .= "ss";
}

if ($status_filter && $status_filter !== 'All') {
    $where .= " AND state = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$date_range = ($date_from && $date_to) ? "$date_from to $date_to" : "All Time";

// Get counts
$countSql = "SELECT 
    COUNT(*) as total,
    SUM(state = 'New') as new_count,
    SUM(state = 'Bug') as bug_count,
    SUM(state = 'Open') as open_count,
    SUM(state = 'In Progress') as in_progress_count,
    SUM(state = 'Resolved') as resolved_count
    FROM issues $where";

$stmt = $conn->prepare($countSql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$counts = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Save report
$saveSql = "INSERT INTO reports (generated_by, date_range, status_filter, total_issues, new_count, bug_count, open_count, in_progress_count, resolved_count)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$saveStmt = $conn->prepare($saveSql);
$saveStmt->bind_param("issiiiiii",
    $generated_by,
    $date_range,
    $status_filter,
    $counts['total'],
    $counts['new_count'],
    $counts['bug_count'],
    $counts['open_count'],
    $counts['in_progress_count'],
    $counts['resolved_count']
);
$saveStmt->execute();
$saveStmt->close();

// Get issues for the report
$issuesSql = "SELECT i.*, u1.name AS issued_by_name, u2.name AS assigned_to_name
    FROM issues i
    LEFT JOIN users u1 ON i.issued_by = u1.id
    LEFT JOIN users u2 ON i.assigned_to = u2.id
    $where ORDER BY i.created_at DESC";

$issuesStmt = $conn->prepare($issuesSql);
if (!empty($params)) {
    $issuesStmt->bind_param($types, ...$params);
}
$issuesStmt->execute();
$issuesResult = $issuesStmt->get_result();

$issues = [];
while ($row = $issuesResult->fetch_assoc()) {
    $issues[] = $row;
}
$issuesStmt->close();

echo json_encode([
    'success' => true,
    'data' => [
        'counts' => $counts,
        'issues' => $issues,
        'dateRange' => $date_range,
        'statusFilter' => $status_filter
    ]
]);

$conn->close();
?>