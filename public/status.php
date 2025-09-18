<?php

require __DIR__ . '/../config.php';

$jobId = $_GET['job_id'] ?? '';
if (!$jobId) {
    echo json_encode(['status' => 'error', 'message' => 'Missing job_id']);
    exit;
}

$jobKey = "job:$jobId";
$job = $redis->hgetall($jobKey);
$messages = $redis->lrange("$jobKey:messages", 0, -1);

if (!$job) {
    echo json_encode(['status' => 'error', 'message' => 'Job not found']);
    exit;
}

// Include messages in the JSON
$job['messages'] = $messages;

echo json_encode($job);
