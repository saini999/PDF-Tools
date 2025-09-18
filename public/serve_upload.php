<?php
require __DIR__ . '/../config.php';

if (!isset($_GET['file'])) {
    http_response_code(400);
    exit("Missing file parameter");
}

$filename = basename($_GET['file']); // prevent directory traversal
$path = UPLOAD_DIR . $filename;

if (!file_exists($path)) {
    http_response_code(404);
    exit("File not found");
}

// Detect MIME type
$mime = mime_content_type($path);
header("Content-Type: $mime");
header("Content-Length: " . filesize($path));
readfile($path);
exit;
