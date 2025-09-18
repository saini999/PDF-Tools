<?php
require '../config.php';

if (!isset($_FILES['photo'])) {
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded']);
    exit;
}

$targetSize = isset($_POST['target_size']) ? intval($_POST['target_size']) : 50;
$uuid = uniqid('passport_', true);
$uploadPath = UPLOAD_DIR . $uuid . '.jpg';
$previewPath = UPLOAD_DIR . $uuid . '_preview.jpg';

move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath);

// Run Python face detection
$jsonPath = UPLOAD_DIR . $uuid . '_coords.json';
$cmd = "python3 " . __DIR__ . "/face_detect.py " . escapeshellarg($uploadPath) . " " . escapeshellarg($jsonPath);
exec($cmd);

$coords = [];
if (file_exists($jsonPath)) {
    $coords = json_decode(file_get_contents($jsonPath), true);
}
$imageUrl = '/serve_upload.php?file=' . basename($uploadPath);
$response = [
    'status' => 'ok',
    'job_id' => $uuid,
    'image_url' => $imageUrl,
    'crop_box' => isset($coords['x']) ? $coords : null
];

echo json_encode($response);