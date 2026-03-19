<?php
require_once '../config/database.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$conn = getConnection();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get all issues based on report filters
$reportStmt = $conn->prepare("SELECT * FROM reports WHERE id = ? OR ? = 0 ORDER BY generated_at DESC LIMIT 1");
$reportStmt->bind_param("ii", $id, $id);
$reportStmt->execute();
$report = $reportStmt->get_result()->fetch_assoc();
$reportStmt->close();

// Get issues
$sql = "SELECT i.*, u1.name AS issued_by_name, u2.name AS assigned_to_name
        FROM issues i
        LEFT JOIN users u1 ON i.issued_by = u1.id
        LEFT JOIN users u2 ON i.assigned_to = u2.id
        ORDER BY i.created_at DESC";

$result = $conn->query($sql);
$issues = [];
while ($row = $result->fetch_assoc()) {
    $issues[] = $row;
}

$conn->close();

// Create spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Issues Report');

// Header row styling
$headers = ['#', 'Dashboard', 'Module', 'Description', 'State', 'Priority', 'Issued By', 'Assigned To', 'Date', 'Source'];
foreach ($headers as $col => $header) {
    $cell = chr(65 + $col) . '1';
    $sheet->setCellValue($cell, $header);
    $sheet->getStyle($cell)->getFont()->setBold(true);
    $sheet->getStyle($cell)->getFill()
        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
        ->getStartColor()->setRGB('CC0000');
    $sheet->getStyle($cell)->getFont()->getColor()->setRGB('FFFFFF');
}

// Data rows
foreach ($issues as $rowIndex => $issue) {
    $row = $rowIndex + 2;
    $sheet->setCellValue("A$row", $issue['id']);
    $sheet->setCellValue("B$row", $issue['dashboard'] ?? '—');
    $sheet->setCellValue("C$row", $issue['module'] ?? '—');
    $sheet->setCellValue("D$row", $issue['description']);
    $sheet->setCellValue("E$row", $issue['state']);
    $sheet->setCellValue("F$row", $issue['priority']);
    $sheet->setCellValue("G$row", $issue['issued_by_name'] ?? '—');
    $sheet->setCellValue("H$row", $issue['assigned_to_name'] ?? '—');
    $sheet->setCellValue("I$row", $issue['date_identified']);
    $sheet->setCellValue("J$row", $issue['source']);

    // Alternate row colors
    if ($rowIndex % 2 === 0) {
        $sheet->getStyle("A$row:J$row")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('FFF5F5');
    }
}

// Auto size columns
foreach (range('A', 'J') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Output file
$filename = 'IssueTracker_Report_' . date('Y-m-d') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
?>