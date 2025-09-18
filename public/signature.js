(function () {
  let cropper;
  let signatureJobId = null;
  let signatureStatsInterval = null;

  // DOM elements
  let form,
    btn,
    statusDiv,
    input,
    cropContainer,
    previewImg,
    confirmBtn,
    logsPopup,
    logsList,
    closeLogs,
    colorPicker;

  function appendSignatureLog(msg) {
    if (!logsPopup || !logsList) return;
    logsPopup.classList.remove("hidden");
    const li = document.createElement("li");
    li.textContent = msg;
    logsList.appendChild(li);
    logsList.scrollTop = logsList.scrollHeight;
  }

  async function pollSignatureLogs(jobId) {
    try {
      const res = await fetch(`signature_status.php?job_id=${jobId}`);
      const json = await res.json();
      if (json.messages) {
        json.messages.forEach((msg) => appendSignatureLog(msg));
      }
      if (json.status !== "done" && json.status !== "error") {
        setTimeout(() => pollSignatureLogs(jobId), 1000);
      } else if (json.status === "done") {
        appendSignatureLog("Extraction complete!");
      } else if (json.status === "error") {
        appendSignatureLog("Error: " + json.message);
      }
    } catch (err) {
      appendSignatureLog("Error fetching logs: " + err.message);
    }
  }

  function initSignatureTab() {
    // Avoid re-initializing
    if (window.signatureInitialized) return;
    window.signatureInitialized = true;

    form = document.getElementById("signatureForm");
    btn = document.getElementById("signatureUploadBtn");
    statusDiv = document.getElementById("signatureStatus");
    input = document.getElementById("signatureInput");
    cropContainer = document.getElementById("signatureCropContainer");
    previewImg = document.getElementById("signaturePreviewImg");
    confirmBtn = document.getElementById("signatureConfirmCrop");
    logsPopup = document.getElementById("signatureProgressPopup");
    logsList = document.getElementById("signatureProgressList");
    closeLogs = document.getElementById("signatureCloseLogs");
    colorPicker = document.getElementById("colorPickerContainer");

    closeLogs?.addEventListener("click", () =>
      logsPopup?.classList.add("hidden")
    );

    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      const data = new FormData(form);

      btn.disabled = true;
      btn.textContent = "Uploading…";
      statusDiv.textContent = "Uploading signature…";
      logsList.innerHTML = "";

      try {
        const res = await fetch("signature_upload.php", {
          method: "POST",
          body: data,
        });
        const json = await res.json();
        if (json.status === "ok") {
          signatureJobId = json.job_id;
          previewImg.src = json.image_url;
          cropContainer.classList.remove("hidden");
          colorPicker.classList.remove("hidden");
          btn.style.display = "none";

          if (cropper) cropper.destroy();
          cropper = new Cropper(previewImg, {
            viewMode: 1,
            autoCropArea: 1,
            background: true,
            responsive: true,
          });
        } else {
          statusDiv.textContent = "Error: " + json.message;
        }
      } catch (err) {
        statusDiv.textContent = "Upload failed: " + err.message;
      }
    });

    confirmBtn.addEventListener("click", async () => {
      if (!cropper || !signatureJobId) return;
      statusDiv.textContent = "Extracting signature…";

      const cropData = cropper.getData();
      const canvas = cropper.getCroppedCanvas();
      canvas.toBlob(async (blob) => {
        const fd = new FormData();
        fd.append("job_id", signatureJobId);
        fd.append("crop", blob);
        fd.append("bgColor", document.getElementById("bgColorInput").value);
        fd.append(
          "target_size",
          document.getElementById("signatureTargetSize").value
        );

        try {
          const res = await fetch("signature_process.php", {
            method: "POST",
            body: fd,
          });
          const json = await res.json();

          if (json.status === "ok") {
            appendSignatureLog("Processing started…");
            pollSignatureLogs(signatureJobId);

            confirmBtn.textContent = "Download Signature";
            confirmBtn.classList.remove("bg-green-600");
            confirmBtn.classList.add("bg-blue-600");
            confirmBtn.onclick = () => {
              window.open(json.download_url, "_blank");
              setTimeout(() => location.reload(), 2000);
            };
          } else {
            statusDiv.textContent = "Error: " + json.message;
          }
        } catch (err) {
          statusDiv.textContent = "Processing failed: " + err.message;
        }
      }, "image/png");
    });
  }

  window.initSignatureTab = initSignatureTab;
})();
