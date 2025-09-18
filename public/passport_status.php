<?php
require '../config.php';

$jobId = $_GET['job_id'] ?? '';
if (!$jobId) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'No job ID specified']);
    exit;
}

$jobKey = "job:$jobId";
$job = $redis->hgetall($jobKey);

// Ensure messages is an array
$messages = $redis->lrange("$jobKey:messages", -50, -1) ?: [];

$status = $job['status'] ?? 'queued';

echo json_encode([
    'status' => $status,
    'messages' => $messages
]);