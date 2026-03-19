<?php
require_once '../config/database.php';

$conn = getConnection();

$input = json_decode(file_get_contents('php://input'), true);

$issue_id = !empty($input['issueId']) ? intval($input['issueId']) : 0;
$user_id  = !empty($input['userId']) ? intval($input['userId']) : null;
$comment  = isset($input['commentText']) ? trim($input['commentText']) : '';

if (!$issue_id || empty($comment)) {
    echo json_encode(['success' => false, 'message' => 'issue_id and comment are required.']);
    exit();
}

$stmt = $conn->prepare("INSERT INTO comments (issue_id, user_id, comment) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $issue_id, $user_id, $comment);
$stmt->execute();
$newId = $conn->insert_id;
$stmt->close();

// Return the new comment with user name
$getStmt = $conn->prepare("SELECT c.*, u.name AS user_name FROM comments c LEFT JOIN users u ON c.user_id = u.id WHERE c.id = ?");
$getStmt->bind_param("i", $newId);
$getStmt->execute();
$newComment = $getStmt->get_result()->fetch_assoc();
$getStmt->close();

echo json_encode(['success' => true, 'data' => $newComment, 'message' => 'Comment added successfully.']);

$conn->close();
?>