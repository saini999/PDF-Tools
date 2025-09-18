<?php require __DIR__ . '/../config.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Passport Photo Cropper</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/2.0.0-alpha.2/cropper.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/2.0.0-alpha.2/cropper.min.js"></script>
    <style>
        #progressPopup {
            max-height: 200px;
            overflow-y: auto;
            background-color: #1f2937;
            /* dark console background */
            color: #f9fafb;
            /* light text */
            padding: 0.75rem;
            border-radius: 0.75rem;
            font-family: monospace;
            font-size: 0.875rem;
            margin-top: 1rem;
        }

        .downscale-btn .icon {
            margin-right: 0.5em;
            font-size: 1.2em;
            display: inline-block;
            width: 1.2em;
            text-align: center;
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-500 to-purple-600 font-sans">
    <div class="flex-grow flex items-center justify-center">
        <div class="bg-white p-8 rounded-2xl shadow-2xl max-w-lg w-full text-center">
            <h1 class="text-2xl font-bold mb-4">Passport Photo Extractor</h1>
            <p class="mb-6 text-gray-600">Upload your image (JPG/PNG). Face will be auto-detected, then you can adjust
                crop area.</p>

            <form id="passportForm" enctype="multipart/form-data" class="space-y-4" action="passport_upload.php">
                <input type="file" id="photoInput" name="photo" accept="image/jpeg,image/png" required
                    class="block w-full text-gray-600 border rounded p-2" />
                <div>
                    <label for="passport_target_size" class="block text-sm text-gray-500 mb-1">Target Size (KB)</label>
                    <input type="number" name="target_size" id="passport_target_size" value="40"
                        class="block w-full text-gray-600 border rounded p-2" required />
                </div>
                <!-- Downscale toggle button -->
                <div class="bg-white rounded-lg shadow-lg p-4 text-gray-700 text-sm">
                    <button type="button" id="enable_downscale_btn" class="text-white hover:text-white border border-red-700 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center me-2 mb-2">
                        <span class="icon" id="enable_downscale_icon">&#10006;</span>
                        Downscaling
                    </button>
                    <input type="hidden" name="enable_downscale" id="enable_downscale" value="0" />
                    <p>Downscales Images (Reduces Resolution) instead of Compressing</p>
                </div>
                <button type="submit" id="processBtn"
                    class="w-full h-14 rounded-xl bg-indigo-600 text-white font-semibold text-lg">Upload &
                    Detect</button>
            </form>

            <div class="mt-6 hidden" id="cropContainer">
                <h2 class="text-lg font-semibold mb-2">Adjust Crop</h2>
                <div class="w-full border rounded overflow-hidden">
                    <img id="previewImg" src="" alt="Preview" />
                </div>
                <button id="confirmCrop"
                    class="mt-4 w-full h-12 rounded-xl bg-green-600 text-white font-semibold">Confirm & Save</button>
            </div>

            <div id="passportStats" class="bg-white rounded-lg shadow-lg p-4 text-gray-700 text-sm hidden">
                <h2 class="font-semibold text-lg mb-2 text-indigo-600">ðŸ“Š Stats</h2>
                <p><strong>Total Extracted:</strong> <span id="passportTotalConverted">0</span></p>
                <p><strong>Extracted Today:</strong> <span id="passportTodayConverted">0</span></p>
                <p><strong>Current Queue:</strong> <span id="passportQueueCount">0</span></p>

                <!-- Status & Logs -->
                <div id="passpoerStatus" class="mt-4 text-gray-700 text-left"></div>
                <div id="passportProgressPopup"
                    class="mt-4 bg-black text-white p-4 rounded-lg max-h-40 overflow-y-auto hidden">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="text-sm font-semibold">Passport Logs</h3>
                        <button id="passportCloseLogs"
                            class="text-gray-300 hover:text-white text-xl leading-none">&times;</button>
                    </div>
                    <ul id="passportProgressList" class="text-xs list-none space-y-1"></ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Example: function to append messages to log
        function appendLog(msg) {
            const logDiv = document.getElementById('passportprogressPopup');
            logDiv.classList.remove('hidden');
            const line = document.createElement('div');
            line.textContent = msg;
            logDiv.appendChild(line);
            logDiv.scrollTop = logDiv.scrollHeight;
        }
    </script>
</body>

</html>