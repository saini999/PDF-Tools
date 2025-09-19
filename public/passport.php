<style>
    .signature-preview-container {
        max-width: 600px;
        /* limit width */
        max-height: 400px;
        /* limit height */
        overflow: auto;
        /* scroll if larger */
        border: 1px solid #ddd;
        border-radius: 0.5rem;
        margin: auto;
        /* center in container */
        background: #f9f9f9;
    }

    .signature-preview-container img {
        max-width: 100%;
        height: auto;
        display: block;
        margin: auto;
    }

    .icon {
        margin-right: 0.5em;
        font-size: 1.2em;
        display: inline-block;
        width: 1.2em;
        text-align: center;
    }
</style>

<div class="flex-grow flex items-center justify-center">
    <div class="bg-white p-8 rounded-2xl shadow-2xl max-w-lg w-full text-center">
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
                    <div class="bg-white rounded-lg shadow-lg p-4 text-gray-700 text-sm">
                        <button type="button" id="enable_png_btn"
                            class="text-white hover:text-white border border-red-700 bg-red-600 hover:bg-green-700 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center me-2 mb-2">
                            <span class="icon" id="enable_png_icon">&#10006;</span>
                            PNG Output
                        </button>
                        <input type="hidden" name="output_format" id="output_format" value="jpg" />
                        <p>Outputs PNG(No Background) instead of JPEG (White Background)</p>
                    </div>
                </div>
                <button id="signatureUploadBtn" type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-4 py-2 rounded">
                    Upload & Detect
                </button>
            </form>

            <!-- Crop/preview -->
            <div id="signatureCropContainer" class="hidden space-y-2">
                <div class="signature-preview-container">
                    <img id="signaturePreviewImg" class="border rounded" />
                </div>
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

    </div>
</div>