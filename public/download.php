<?php
require __DIR__ . '/../config.php';

// Get job ID or file parameter
$jobId = $_GET['job_id'] ?? '';
$fileParam = $_GET['file'] ?? '';

if ($jobId) {
    // Fetch job info from Redis
    $jobKey = "job:$jobId";
    $job = $redis->hgetall($jobKey);

    if (!$job || !isset($job['output']) || !file_exists($job['output'])) {
        http_response_code(404);
        echo "File not ready.";
        exit;
    }

    $filepath = $job['output'];
} elseif ($fileParam) {
    // Direct file serve (passport images)
    $filepath = PROCESSED_DIR . $fileParam;
    if (!file_exists($filepath)) {
        http_response_code(404);
        echo "File not found.";
        exit;
    }
} else {
    http_response_code(400);
    echo "No file specified.";
    exit;
}

// Detect MIME type dynamically
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $filepath);
finfo_close($finfo);

$filename = basename($filepath);

// Send headers and file
header('Content-Description: File Transfer');
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filepath));

readfile($filepath);
exit;
