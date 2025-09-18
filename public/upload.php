<?php
require '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

if (!isset($_FILES['files'])) {
    echo json_encode(['status' => 'error', 'message' => 'No files uploaded']);
    exit;
}

$targetSize = isset($_POST['target_size']) ? intval($_POST['target_size']) : 200;
if ($targetSize <= 0) $targetSize = 200;

$uuid = bin2hex(random_bytes(16));
$uploadDir = UPLOAD_DIR;
$processedDir = PROCESSED_DIR;

if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
if (!is_dir($processedDir)) mkdir($processedDir, 0777, true);

$files = $_FILES['files'];
$tmpFiles = [];
$isPdf = false;

// Collect uploaded files
for ($i = 0; $i < count($files['name']); $i++) {
    $name = $files['name'][$i];
    $tmpName = $files['tmp_name'][$i];
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    if ($ext === 'pdf') {
        // If it's a PDF, just use it directly (ignore other files if multiple uploaded)
        $isPdf = true;
        $finalInputPath = $uploadDir . $uuid . '.pdf';
        if (!move_uploaded_file($tmpName, $finalInputPath)) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save PDF']);
            exit;
        }
        break;
    } elseif (in_array($ext, ['jpg', 'jpeg', 'png'])) {
        // Collect image paths for later merge
        $tmpPath = $uploadDir . $uuid . "_$i.$ext";
        if (!move_uploaded_file($tmpName, $tmpPath)) {
            echo json_encode(['status' => 'error', 'message' => "Failed to save image $name"]);
            exit;
        }
        $tmpFiles[] = $tmpPath;
    } else {
        echo json_encode(['status' => 'error', 'message' => "Unsupported file type: $ext"]);
        exit;
    }
}

// If images were uploaded → convert to single PDF
if (!$isPdf) {
    if (empty($tmpFiles)) {
        echo json_encode(['status' => 'error', 'message' => 'No valid files uploaded']);
        exit;
    }

    $finalInputPath = $uploadDir . $uuid . '.pdf';

    try {
        $imagick = new Imagick();

        foreach ($tmpFiles as $img) {
            $page = new Imagick($img);
            $page->setImageFormat('pdf');
            $imagick->addImage($page);
        }

        // Ensure multipage PDF output
        $imagick->setImageFormat('pdf');
        $imagick->writeImages($finalInputPath, true);

        // Clean up temp files
        foreach ($tmpFiles as $f) {
            @unlink($f);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Image to PDF conversion failed: ' . $e->getMessage()]);
        exit;
    }
}

$outputPath = $processedDir . $uuid . '.pdf';

// Push job into Redis
$job = [
    'id' => $uuid,
    'input' => $finalInputPath,
    'output' => $outputPath,
    'stage' => 'queued',
    'progress' => 0,
    'target_size' => $targetSize,
];

$redis->hmset("job:$uuid", $job);
$redis->rpush('pdf_jobs', $uuid);

echo json_encode(['status' => 'ok', 'job_id' => $uuid]);
