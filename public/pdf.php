<?php require __DIR__ . '/../config.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PDF Compressor</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .progress-bar {
      height: 1rem;
      background-color: #4f46e5;
      transition: width 0.2s;
    }
  </style>
</head>

<body class="min-h-screen flex flex-col bg-gradient-to-br from-indigo-500 to-purple-600 font-sans">
  <div class="flex-grow flex items-center justify-center">
    <div class="bg-white p-8 rounded-2xl shadow-2xl max-w-lg w-full text-center">
      <h1 class="text-2xl font-bold mb-4">Compress Your PDF</h1>
      <p class="mb-6 text-gray-600">Upload your document and set your target size (KB).</p>
      <p class="mb-6 text-gray-600">Allowed File Types: PDF, JPG, JPEG, PNG and WebP. <br> Multiple Images will be combined into one pdf while compressing <br> Multiple PDFs will not Work. </p>

      <form id="uploadForm" enctype="multipart/form-data" class="space-y-4">
        <input
          type="file"
          name="files[]"
          accept="application/pdf,image/png,image/jpeg,image/webp, image/jpg"
          multiple
          required
          class="block w-full text-gray-600 border rounded p-2" />

        <div class="relative w-full">
          <label for="target_size" class="absolute -top-3 left-2 text-sm text-gray-500 bg-white px-1">
            Target size in KB
          </label>
          <input
            type="number"
            name="target_size"
            id="target_size"
            value="200"
            class="block w-full text-gray-600 border rounded p-2"
            required />
        </div>
        <button type="submit" id="compressBtn" class="w-full h-14 rounded-xl bg-indigo-600 text-white font-semibold text-lg">Upload & Compress</button>
      </form>

      <div class="mt-4 text-left">
        <div id="progressLabel" class="mb-1 text-gray-700">Progress</div>
        <div class="w-full bg-gray-300 rounded">
          <div id="progressBar" class="progress-bar w-0 rounded"></div>
        </div>
      </div>

      <div id="status" class="mt-4 text-gray-700"></div>

      <!-- Logs directly under container -->
      <div id="progressPopup" class="mt-6 bg-black text-green-400 font-mono text-sm rounded-lg p-3 max-h-56 overflow-y-auto hidden">
        <div class="flex justify-between items-center mb-2">
          <h2 class="font-bold text-white">Logs</h2>
          <button id="closeLogsBtn" class="text-gray-400 hover:text-white">&times;</button>
        </div>
        <ul id="progressList" class="space-y-1"></ul>
      </div>
      <button id="toggleLogsBtn" class="mt-3 hidden bg-indigo-600 text-white px-3 py-1 rounded-lg shadow">View Logs</button>


      <div id="statsPanel" class="bottom-6 right-6 bg-white p-4 rounded-2xl shadow-xl text-gray-700 text-sm" style="margin-bottom: 4%; margin-right: -1%;">
        <h2 class="font-semibold text-lg mb-2 text-indigo-600">📊 Stats</h2>
        <p><strong>Total Converted:</strong> <span id="totalConverted">0</span></p>
        <p><strong>Converted Today:</strong> <span id="todayConverted">0</span></p>
        <p><strong>Current Queue:</strong> <span id="queueCount">0</span></p>
      </div>



    </div>

  </div>








</body>

</html>