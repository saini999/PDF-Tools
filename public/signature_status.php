<?php
require '../config.php';

if (!isset($_GET['job_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing job_id']);
    exit;
}

$jobId = $_GET['job_id'];
$jobKey = "job:$jobId";

// Fetch job data
$job = $redis->hgetall($jobKey);

// Fetch messages
$messages = $redis->lrange("$jobKey:messages", 0, -1);

echo json_encode([
    'status'   => $job['status'] ?? 'unknown',
    'progress' => isset($job['progress']) ? intval($job['progress']) : 0,
    'messages' => $messages
]);
