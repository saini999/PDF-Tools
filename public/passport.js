(function () {
  let cropper;
  let jobId = null;
  let passportStatsInterval = null;

  // DOM elements (passport-specific)
  let passportForm,
    passportBtn,
    passportStatus,
    passportPhotoInput,
    passportCropContainer,
    passportPreviewImg,
    passportConfirmBtn,
    passportLogsPopup,
    passportLogsList,
    passportCloseLogs;

  // Append log to Passport log list
  function appendPassportLog(msg) {
    if (!passportLogsPopup || !passportLogsList) return;

    passportLogsPopup.classList.remove("hidden");
    const li = document.createElement("li");
    li.textContent = msg;
    passportLogsList.appendChild(li);

    if (passportLogsList.childElementCount > 500) {
      passportLogsList.removeChild(passportLogsList.firstElementChild);
    }

    passportLogsList.scrollTop = passportLogsList.scrollHeight;
  }

  // Poll server for passport compression logs
  async function pollPassportLogs(jobId) {
    try {
      const res = await fetch(`passport_status.php?job_id=${jobId}`);
      const json = await res.json();
      if (json.messages && Array.isArray(json.messages)) {
        json.messages.forEach((msg) => appendPassportLog(msg));
      }
      if (json.status !== "done" && json.status !== "error") {
        setTimeout(() => pollPassportLogs(jobId), 500);
      } else if (json.status === "done") {
        appendPassportLog("Compression complete!");
      } else if (json.status === "error") {
        appendPassportLog("Compression failed: " + json.message);
      }
    } catch (err) {
      appendPassportLog("Error fetching logs: " + err.message);
    }
  }

  // Update Passport stats
  async function updatePassportStats() {
    try {
      const res = await fetch("passport_stats.php");
      const json = await res.json();
      document.getElementById("passportTotalConverted").textContent =
        json.total;
      document.getElementById("passportTodayConverted").textContent =
        json.today;
      document.getElementById("passportQueueCount").textContent = json.queue;
      document.getElementById("passportStats").classList.remove("hidden");
    } catch (err) {
      console.error("Failed to update Passport stats:", err);
    }
  }

  // Initialize Passport tab
  function initPassportTab() {
    if (window.passportStatsInterval) return;

    // Query DOM elements
    passportForm = document.getElementById("passportForm");
    passportBtn = document.getElementById("processBtn");
    passportStatus = document.getElementById("status");
    passportPhotoInput = document.getElementById("photoInput");
    passportCropContainer = document.getElementById("cropContainer");
    passportPreviewImg = document.getElementById("previewImg");
    passportConfirmBtn = document.getElementById("confirmCrop");
    passportLogsPopup = document.getElementById("passportProgressPopup");
    passportLogsList = document.getElementById("passportProgressList");
    passportCloseLogs = document.getElementById("passportCloseLogs");

    passportCloseLogs?.addEventListener("click", () => {
      passportLogsPopup?.classList.add("hidden");
    });

    if (!passportForm || !passportBtn || !passportStatus) return;

    // Step 1: Upload + detect
    passportForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      const data = new FormData(passportForm);

      passportBtn.disabled = true;
      passportBtn.textContent = "Uploading…";
      passportStatus.textContent = "Uploading and detecting face…";
      passportLogsList.innerHTML = "";
      passportLogsPopup.classList.remove("hidden");

      try {
        const res = await fetch("passport_upload.php", {
          method: "POST",
          body: data,
        });
        const json = await res.json();

        if (json.status === "ok") {
          jobId = json.job_id;
          passportStatus.textContent = "Face detected, adjust crop above.";
          passportBtn.style.display = "none";

          passportPreviewImg.src = json.image_url;
          passportCropContainer.classList.remove("hidden");

          if (cropper) cropper.destroy();

          cropper = new Cropper(passportPreviewImg, {
            viewMode: 1,
            autoCropArea: 1,
            background: true,
            responsive: true,
            ready() {
              const cropBox = json.crop_box;
              if (cropBox && cropBox.x !== undefined) {
                const imgData = cropper.getImageData();
                const scaleX = imgData.width / imgData.naturalWidth;
                const scaleY = imgData.height / imgData.naturalHeight;
                const padding = 0.5;
                cropper.setCropBoxData({
                  left:
                    Math.max(0, cropBox.x - cropBox.width * 1.2 * padding) *
                    scaleX,
                  top:
                    Math.max(0, cropBox.y - cropBox.height * 1.2 * padding) *
                    scaleY,
                  width: cropBox.width * scaleX * (1 + 2.15 * padding),
                  height: cropBox.height * scaleY * (1 + 2.5 * padding),
                });
              }
            },
          });
        } else {
          passportBtn.disabled = false;
          passportBtn.textContent = "Upload & Detect";
          passportStatus.textContent = "Error: " + json.message;
        }
      } catch (err) {
        passportStatus.textContent = "Upload failed: " + err.message;
      }
    });

    // Step 2: Confirm crop & start compression
    passportConfirmBtn.addEventListener("click", async () => {
      if (!cropper || !jobId) return;

      passportStatus.textContent = "Processing final passport photo…";
      passportLogsList.innerHTML = "";
      passportLogsPopup.classList.remove("hidden");

      const cropData = cropper.getData();
      const canvas = cropper.getCroppedCanvas({
        width: Math.round(cropData.width),
        height: Math.round(cropData.height),
      });

      canvas.toBlob(async (blob) => {
        const fd = new FormData();
        fd.append("job_id", jobId);
        fd.append("crop", blob);
        fd.append(
          "target_size",
          document.getElementById("passport_target_size").value
        );

        try {
          const res = await fetch("passport_process.php", {
            method: "POST",
            body: fd,
          });
          const json = await res.json();

          if (json.status === "ok") {
            appendPassportLog("Upload complete. Compression started…");
            pollPassportLogs(jobId);

            // Update confirm button to act as download
            passportConfirmBtn.textContent = "Download Passport Photo";
            passportConfirmBtn.classList.remove("bg-green-600");
            passportConfirmBtn.classList.add("bg-blue-600");
            passportConfirmBtn.disabled = false;

            // Change click behavior to download & reset
            passportConfirmBtn.onclick = () => {
              window.open(json.download_url, "_blank");
              setTimeout(() => location.reload(), 2000);
            };
          } else {
            passportStatus.textContent = "Error: " + json.message;
            appendPassportLog("Error: " + json.message);
          }
        } catch (err) {
          passportStatus.textContent = "Processing failed: " + err.message;
          appendPassportLog("Processing failed: " + err.message);
        }
      }, "image/jpeg");
    });

    // Start Passport stats polling
    updatePassportStats();
    window.passportStatsInterval = setInterval(updatePassportStats, 1000);
  }

  // Export initializer
  window.initPassportTab = initPassportTab;
})();
