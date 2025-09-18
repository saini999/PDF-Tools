<?php
require '../config.php';

if (!isset($_POST['job_id']) || !isset($_FILES['crop']) || !isset($_POST['bgColor']) || !isset($_POST['target_size'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$jobId = $_POST['job_id'];
$bgColor = $_POST['bgColor']; // hex color
$targetSize = intval($_POST['target_size']) * 1024; // convert KB → bytes
$jobKey = "job:$jobId";

$outputPath = PROCESSED_DIR . $jobId . '.png';
move_uploaded_file($_FILES['crop']['tmp_name'], $outputPath);

function logProgress($msg, $jobKey)
{
    global $redis;
    $redis->rpush("$jobKey:messages", $msg);
    $redis->ltrim("$jobKey:messages", -200, -1);
}

try {
    $img = new Imagick($outputPath);

    // Convert hex to ImagickPixel
    $removeColor = new ImagickPixel($bgColor);
    logProgress("Selected background color: $bgColor", $jobKey);

    // Enhance before removal
    $img->contrastImage(1);
    $img->normalizeImage();
    logProgress("Enhanced image contrast/normalized", $jobKey);

    // Remove chosen background color with tolerance
    $img->setImageAlphaChannel(Imagick::ALPHACHANNEL_SET);
    $fuzz = 0.30 * Imagick::getQuantumRange()['quantumRangeLong']; // 30% tolerance
    $img->transparentPaintImage($removeColor, 0, $fuzz, false);
    logProgress("Removed background with fuzz tolerance", $jobKey);

    // Ensure PNG output (with transparency)
    $img->setImageFormat("png");
    $img->writeImage($outputPath);
    clearstatcache();
    $currentSize = filesize($outputPath);

    logProgress("Initial PNG size: " . round($currentSize / 1024) . " KB", $jobKey);
    logProgress("Target size: " . round($targetSize / 1024) . " KB", $jobKey);

    // If too small → upscale
    if ($currentSize < $targetSize * 0.95) {
        logProgress("Upscaling signature to reach target size…", $jobKey);
        $scaleFactor = 1.1;
        while ($currentSize < $targetSize * 0.95) {
            $width = $img->getImageWidth();
            $height = $img->getImageHeight();
            $newWidth = intval($width * $scaleFactor);
            $newHeight = intval($height * $scaleFactor);
            $img->resizeImage($newWidth, $newHeight, Imagick::FILTER_LANCZOS, 1);
            $img->writeImage($outputPath);
            clearstatcache();
            $currentSize = filesize($outputPath);
            logProgress("Upscaled to {$newWidth}x{$newHeight} → " . round($currentSize / 1024) . " KB", $jobKey);

            if ($newWidth > 5000 || $newHeight > 5000) {
                logProgress("Stopped upscaling (max size reached)", $jobKey);
                break;
            }
        }
    }

    // If too large → compress
    if ($currentSize > $targetSize * 1.05) {
        logProgress("Compressing signature PNG…", $jobKey);
        while ($currentSize > $targetSize * 1.05) {
            $width = $img->getImageWidth();
            $height = $img->getImageHeight();
            $img->resizeImage(intval($width * 0.9), intval($height * 0.9), Imagick::FILTER_LANCZOS, 1);
            $img->writeImage($outputPath);
            clearstatcache();
            $currentSize = filesize($outputPath);
            logProgress("Downscaled to {$width}x{$height} → " . round($currentSize / 1024) . " KB", $jobKey);
        }
    }

    $img->writeImage($outputPath);
    $img->destroy();

    // Save job state
    $redis->hmset($jobKey, [
        'stage' => 'done',
        'status' => 'done',
        'progress' => 100,
        'completed_at' => time(),
        'output' => $outputPath,
        'input' => $outputPath
    ]);

    logProgress("Signature extraction + compression complete!", $jobKey);

    echo json_encode([
        'status' => 'ok',
        'download_url' => 'download.php?file=' . basename($outputPath)
    ]);

    // Stats
    $redis->incr('signature_total');
    $todayKey = 'signature_today:' . date('Y-m-d');
    $redis->incr($todayKey);
    $redis->expire($todayKey, 86400);
} catch (Exception $e) {
    logProgress("Error: " . $e->getMessage(), $jobKey);
    $redis->hmset($jobKey, ['status' => 'error']);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
