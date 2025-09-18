<?php
require '../config.php';

header('Content-Type: application/json');

if (!isset($_FILES['signature'])) {
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded']);
    exit;
}

$jobId = uniqid('signature_', true);
$jobKey = "job:$jobId";

// Save uploaded file
$uploadPath = UPLOAD_DIR . $jobId . '.jpg';
if (!move_uploaded_file($_FILES['signature']['tmp_name'], $uploadPath)) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to save upload']);
    exit;
}

$imageUrl = '/serve_upload.php?file=' . basename($uploadPath);

// Store basic job info in Redis
$redis->hmset($jobKey, [
    'id'       => $jobId,
    'status'   => 'uploaded',
    'input'    => $uploadPath,
    'created_at' => time()
]);

// Clean old logs if any
$redis->del("$jobKey:messages");

// Return preview URL
echo json_encode([
    'status' => 'ok',
    'job_id' => $jobId,
    'image_url' => $imageUrl
]);
