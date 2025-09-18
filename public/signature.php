<div id="signatureTool" class="space-y-4">
  <h2 class="text-2xl font-bold text-gray-700">Signature Extractor</h2>
  <p class="text-gray-600">Upload a scanned signature and we’ll automatically remove the background.</p>

  <!-- Upload form -->
  <form id="signatureForm" class="space-y-4">
    <input type="file" id="signatureInput" name="signature" accept="image/*" required
      class="block w-full border p-2 rounded">
    <div id="colorPickerContainer" class="hidden">
      <label for="bgColor">Background color to remove:</label>
      <input type="color" id="bgColorInput" name="bgColor" required value="">
      <!-- Target size input -->
      <div class="space-y-1">
        <label for="signatureTargetSize" class="block text-sm font-medium text-gray-700">
          Target File Size (KB)
        </label>
        <input type="number" id="signatureTargetSize" name="target_size" value="25" min="1"
          class="block w-32 border p-2 rounded" required>
        <p class="text-xs text-gray-500">Signature will be compressed/enlarged close to this size.</p>
      </div>
    </div>
    <button id="signatureUploadBtn" type="submit"
      class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-4 py-2 rounded">
      Upload & Detect
    </button>
  </form>

  <!-- Crop/preview -->
  <div id="signatureCropContainer" class="hidden space-y-2">
    <img id="signaturePreviewImg" class="max-w-full border rounded" />
    <button id="signatureConfirmCrop"
      class="bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded">
      Confirm & Extract
    </button>
  </div>

  <!-- Status -->
  <div id="signatureStatus" class="text-sm text-gray-600"></div>

  <!-- Logs -->
  <div id="signatureProgressPopup" class="mt-4 bg-black text-white p-4 rounded-lg max-h-40 overflow-y-auto hidden">
    <div class="flex justify-between items-center mb-2">
      <h3 class="font-medium">Processing Logs</h3>
      <button id="signatureCloseLogs" class="text-red-500 text-sm">[x]</button>
    </div>
    <ul id="signatureProgressList" class="text-xs space-y-1"></ul>
  </div>
</div>