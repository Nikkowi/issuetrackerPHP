<?php
require_once '../config/database.php';

$conn = getConnection();

$total      = $conn->query("SELECT COUNT(*) as count FROM issues")->fetch_assoc()['count'];
$new        = $conn->query("SELECT COUNT(*) as count FROM issues WHERE state = 'New'")->fetch_assoc()['count'];
$bugs       = $conn->query("SELECT COUNT(*) as count FROM issues WHERE state = 'Bug'")->fetch_assoc()['count'];
$open       = $conn->query("SELECT COUNT(*) as count FROM issues WHERE state = 'Open'")->fetch_assoc()['count'];
$inProgress = $conn->query("SELECT COUNT(*) as count FROM issues WHERE state = 'In Progress'")->fetch_assoc()['count'];
$resolved   = $conn->query("SELECT COUNT(*) as count FROM issues WHERE state = 'Resolved'")->fetch_assoc()['count'];

// Issues by priority
$priorityResult = $conn->query("SELECT priority, COUNT(*) as count FROM issues GROUP BY priority");
$byPriority = [];
while ($row = $priorityResult->fetch_assoc()) {
    $byPriority[] = $row;
}

// Issues by dashboard
$dashboardResult = $conn->query("SELECT dashboard, COUNT(*) as count FROM issues WHERE dashboard IS NOT NULL GROUP BY dashboard ORDER BY count DESC");
$byDashboard = [];
while ($row = $dashboardResult->fetch_assoc()) {
    $byDashboard[] = $row;
}

// Recent issues
$recentResult = $conn->query("SELECT i.*, u1.name AS issued_by_name, u2.name AS assigned_to_name
    FROM issues i
    LEFT JOIN users u1 ON i.issued_by = u1.id
    LEFT JOIN users u2 ON i.assigned_to = u2.id
    ORDER BY i.created_at DESC LIMIT 5");
$recent = [];
while ($row = $recentResult->fetch_assoc()) {
    $recent[] = $row;
}

echo json_encode([
    'success' => true,
    'data' => [
        'total'      => $total,
        'new'        => $new,
        'bugs'       => $bugs,
        'open'       => $open,
        'inProgress' => $inProgress,
        'resolved'   => $resolved,
        'byPriority' => $byPriority,
        'byDashboard'=> $byDashboard,
        'recent'     => $recent
    ]
]);

$conn->close();
?>