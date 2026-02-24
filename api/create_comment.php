<?php
require_once '../config/database.php';

$conn = getConnection();

$input = json_decode(file_get_contents('php://input'), true);

$issue_id = !empty($input['issueId']) ? intval($input['issueId']) : 0;
$user_id  = !empty($input['userId']) ? intval($input['userId']) : null;
$comment  = $input['commentText'] ?? '';

if (!$issue_id || empty($comment)) {
    echo json_encode(['success' => false, 'message' => 'issue_id and comment are required.']);
    exit();
}

$stmt = $conn->prepare("INSERT INTO comments (issue_id, user_id, comment_text) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $issue_id, $user_id, $comment);
$stmt->execute();

$newId = $conn->insert_id;

echo json_encode(['success' => true, 'data' => ['id' => $newId], 'message' => 'Comment added successfully.']);

$stmt->close();
$conn->close();
?>
