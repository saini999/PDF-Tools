<?php
require '../config.php';

if (!isset($_POST['job_id']) || !isset($_FILES['crop'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$jobId = $_POST['job_id'];
$targetSize = isset($_POST['target_size']) ? intval($_POST['target_size']) * 1024 : 50 * 1024;
$jobKey = "job:$jobId";

// Paths
$inputPath = $_FILES['crop']['tmp_name'];
$outputPath = PROCESSED_DIR . $jobId . '.jpg';

// Move uploaded crop to processed folder (treat as input reference)
move_uploaded_file($inputPath, $outputPath);

// Initialize Redis job record early (so cleaner can see it even if process crashes later)
$redis->hmset($jobKey, [
    'id'         => $jobId,
    'status'     => 'processing',
    'stage'      => 'passport',
    'input'      => $outputPath,  // we treat first saved file as input reference
    'output'     => $outputPath,  // will be overwritten after processing
    'created_at' => time(),
    'progress'   => 0
]);

function logProgress($msg, $jobKey)
{
    global $redis;
    $redis->rpush("$jobKey:messages", $msg);
    $redis->ltrim("$jobKey:messages", -50, -1);
}

$img = new Imagick($outputPath);
$img->setImageCompression(Imagick::COMPRESSION_JPEG);

// Step 1: Get current size
$currentSize = filesize($outputPath);
logProgress("Uploaded image size: " . round($currentSize / 1024) . " KB", $jobKey);
logProgress("Target Size: " . round($targetSize / 1024) . " KB", $jobKey);

// Step 2: Upscale if needed
if ($currentSize < $targetSize * 0.95) {
    logProgress("Upscaling image to approach target size...", $jobKey);
    $scaleFactor = 1.1; // 10% upscale each iteration
    while ($currentSize < $targetSize * 0.95) {
        $width = $img->getImageWidth();
        $height = $img->getImageHeight();
        $newWidth = intval($width * $scaleFactor);
        $newHeight = intval($height * $scaleFactor);
        $img->resizeImage($newWidth, $newHeight, Imagick::FILTER_LANCZOS, 1);
        $img->writeImage($outputPath);
        clearstatcache();
        $currentSize = filesize($outputPath);
        logProgress("Upscaled to {$newWidth}x{$newHeight} → size: " . round($currentSize / 1024) . " KB", $jobKey);
    }
}

// Step 3: Compress only if needed
if ($currentSize > $targetSize * 1.05) {
    logProgress("Starting compression...", $jobKey);
    $minQ = 10;
    $maxQ = 95;
    $bestQuality = $maxQ;
    $bestDiff = PHP_INT_MAX;

    for ($q = $maxQ; $q >= $minQ; $q -= 5) {
        $img->setImageCompressionQuality($q);
        $img->writeImage($outputPath);
        clearstatcache();
        $size = filesize($outputPath);

        logProgress("Trying quality $q → size: " . round($size / 1024) . " KB", $jobKey);

        $diff = abs($size - $targetSize);
        if ($diff < $bestDiff) {
            $bestDiff = $diff;
            $bestQuality = $q;
        }

        // Stop early if within 95% of target
        if ($size <= $targetSize * 1.05 && $size >= $targetSize * 0.95) {
            logProgress("Target size reached at quality $q", $jobKey);
            break;
        }
    }

    logProgress("Final quality: $bestQuality", $jobKey);
    $img->setImageCompressionQuality($bestQuality);
    $img->writeImage($outputPath);
} else {
    logProgress("No compression needed, already within target size.", $jobKey);
}

$img->destroy();

// Step 4: Finalize
$redis->hmset($jobKey, [
    'stage'        => 'done',
    'status'       => 'done',
    'progress'     => 100,
    'completed_at' => time(),
    'input'        => $outputPath,  // keep reference for cleaner
    'output'       => $outputPath
]);

logProgress("Compression complete!", $jobKey);

echo json_encode([
    'status' => 'ok',
    'download_url' => 'download.php?file=' . basename($outputPath)
]);

$redis->incr('passport_total');
$todayKey = 'passport_today:' . date('Y-m-d');
$redis->incr($todayKey);
$redis->expire($todayKey, 86400); // expire after 24 hours
