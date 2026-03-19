<?php
require_once '../config/database.php';

$conn = getConnection();

$input = json_decode(file_get_contents('php://input'), true);

$generated_by  = !empty($input['generatedBy']) ? intval($input['generatedBy']) : null;
$date_range    = $input['dateRange'] ?? 'All Time';
$status_filter = $input['statusFilter'] ?? 'All Statuses';

$where = "WHERE 1=1";
$params = [];
$types = "";

if ($date_range === 'Last 30 Days') {
    $where .= " AND date_identified >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
} elseif ($date_range === 'Last 7 Days') {
    $where .= " AND date_identified >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
} elseif ($date_range === 'This Year') {
    $where .= " AND YEAR(date_identified) = YEAR(CURDATE())";
}

if ($status_filter !== 'All Statuses' && $status_filter !== 'All') {
    $where .= " AND state = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$countSql = "SELECT 
    COUNT(*) as total_issues,
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

$saveSql = "INSERT INTO reports (generated_by, date_range, status_filter, total_issues, new_count, bug_count, open_count, in_progress_count, resolved_count)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$saveStmt = $conn->prepare($saveSql);
$saveStmt->bind_param("issiiiiii",
    $generated_by,
    $date_range,
    $status_filter,
    $counts['total_issues'],
    $counts['new_count'],
    $counts['bug_count'],
    $counts['open_count'],
    $counts['in_progress_count'],
    $counts['resolved_count']
);
$saveStmt->execute();
$saveStmt->close();

echo json_encode([
    'success' => true,
    'data' => [
        'total_issues'      => $counts['total_issues'],
        'new_count'         => $counts['new_count'],
        'bug_count'         => $counts['bug_count'],
        'open_count'        => $counts['open_count'],
        'in_progress_count' => $counts['in_progress_count'],
        'resolved_count'    => $counts['resolved_count'],
        'date_range'        => $date_range,
        'status_filter'     => $status_filter
    ]
]);

$conn->close();
?>