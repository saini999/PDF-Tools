<?php
require '../config.php';

define('LOG_FILE', '/home/gaditc/web/tools.gaditc.in/public_html/worker.log');

function logMessage($msg, $jobKey = null)
{
    global $redis;
    $time = date("Y-m-d H:i:s");
    $line = "[$time] $msg\n";

    // Append to logfile
    file_put_contents(LOG_FILE, $line, FILE_APPEND);

    // Also push message to Redis for frontend
    if ($jobKey) {
        $redis->rpush("$jobKey:messages", $msg);
        // Keep only last 50 messages to avoid bloating
        $redis->ltrim("$jobKey:messages", -50, -1);
    }

    // Echo for console output (optional)
    echo $line;
}

function getDpiForLevel($level)
{
    $levels = 100;

    $colorStep = (300 - 30) / ($levels - 1);
    $grayStep  = (300 - 30) / ($levels - 1);
    $monoStep  = (600 - 60) / ($levels - 1);

    $color = round(300 - ($level - 1) * $colorStep);
    $gray  = round(300 - ($level - 1) * $grayStep);
    $mono  = round(600 - ($level - 1) * $monoStep);

    return [$color, $gray, $mono];
}

logMessage("Worker started");

$profiles = ['/prepress'];

while (true) {
    $jobId = $redis->lpop('pdf_jobs');
    if (!$jobId) {
        sleep(1);
        continue;
    }

    $jobKey = "job:$jobId";
    $job = $redis->hgetall($jobKey);

    logMessage("Picked up job $jobId", $jobKey);
    $redis->hmset($jobKey, ['stage' => 'compressing', 'status' => 'compressing', 'progress' => 0]);

    $input = $job['input'];
    $output = $job['output'];
    $targetSize = isset($job['target_size']) ? intval($job['target_size']) * 1024 : 200 * 1024;

    $bestOutput = null;
    $bestSize = 0;
    $allCandidates = [];

    foreach ($profiles as $profile) {
        logMessage("Trying profile $profile", $jobKey);

        $minLevel = 1;
        $maxLevel = 100;

        while ($minLevel <= $maxLevel) {
            $level = intval(($minLevel + $maxLevel) / 2);
            list($colorDPI, $grayDPI, $monoDPI) = getDpiForLevel($level);

            $tempOutput = dirname($output) . '/temp_' . basename($profile, '/') . "_$level.pdf";

            $cmd = "gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.3 "
                . "-dPDFSETTINGS=$profile "
                . "-dNOPAUSE -dQUIET -dBATCH "
                . "-dColorImageDownsampleType=/Average "
                . "-dColorImageResolution=$colorDPI "
                . "-dGrayImageDownsampleType=/Average "
                . "-dGrayImageResolution=$grayDPI "
                . "-dMonoImageDownsampleType=/Subsample "
                . "-dMonoImageResolution=$monoDPI "
                . "-dAutoFilterColorImages=false -dColorImageFilter=/DCTEncode "
                . "-dAutoFilterGrayImages=false -dGrayImageFilter=/DCTEncode "
                . "-sOutputFile=" . escapeshellarg($tempOutput) . " "
                . escapeshellarg($input);

            logMessage("Trying profile $profile level $level: ColorDPI=$colorDPI GrayDPI=$grayDPI MonoDPI=$monoDPI", $jobKey);
            exec($cmd . " 2>&1", $out, $ret);

            if ($ret !== 0 || !file_exists($tempOutput)) {
                logMessage("Ghostscript failed for profile $profile at level $level", $jobKey);
                break;
            }

            $size = filesize($tempOutput);
            $allCandidates[$tempOutput] = $size;

            logMessage("Profile $profile level $level produced output size: " . round($size / 1024) . " KB", $jobKey);

            // Track best candidate ≤ target
            if ($size <= $targetSize && $size > $bestSize) {
                $bestOutput = $tempOutput;
                $bestSize = $size;
                logMessage("New best candidate: profile $profile level $level (" . round($size / 1024) . " KB)", $jobKey);
            }

            // Update progress in Redis
            $redis->hmset($jobKey, ['progress' => intval(($level / 100) * 100)]);

            // Binary search adjustment
            if ($size > $targetSize) {
                logMessage("Output too big, increasing compression...", $jobKey);
                $minLevel = $level + 1;
            } else {
                logMessage("Output under target, try less compression...", $jobKey);
                $maxLevel = $level - 1;
            }
        }

        if ($bestOutput && $bestSize >= $targetSize * 0.9) {
            logMessage("Good candidate found with $profile, stopping profile loop", $jobKey);
            break;
        }
    }

    if ($bestOutput) {
        rename($bestOutput, $output);
        logMessage("Job $jobId compressed successfully to " . round(filesize($output) / 1024) . " KB", $jobKey);

        // increment counters
        $redis->incr("stats:total_converted");
        $redis->incr("stats:converted:" . date("Y-m-d"));

        $redis->hmset($jobKey, [
            'stage' => 'done',
            'status' => 'done',
            'progress' => 100,
            'completed_at' => time()
        ]);
    } else {
        if (!empty($allCandidates)) {
            asort($allCandidates);
            $smallestFile = key($allCandidates);
            $smallestSize = reset($allCandidates);

            rename($smallestFile, $output);

            logMessage("WARNING: Could not reach target size (" . round($targetSize / 1024) . " KB). Using smallest available output: " . round($smallestSize / 1024) . " KB", $jobKey);
            $redis->incr("stats:total_converted");
            $redis->incr("stats:converted:" . date("Y-m-d"));
            $redis->hmset($jobKey, [
                'stage' => 'done',
                'status' => 'warning',
                'message' => "Could not reach target size (" . round($targetSize / 1024) . " KB). Output is " . round($smallestSize / 1024) . " KB",
                'progress' => 100,
                'completed_at' => time()
            ]);
        } else {
            $redis->hmset($jobKey, [
                'stage' => 'error',
                'status' => 'error',
                'message' => 'Compression failed (no output generated)',
                'completed_at' => time()
            ]);
            logMessage("Job $jobId failed (no valid candidate)", $jobKey);
        }
    }

    // Clean up temp files
    foreach (glob(dirname($output) . '/temp_*.pdf') as $tmp) {
        if (file_exists($tmp)) unlink($tmp);
    }
}
