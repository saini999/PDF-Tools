(function () {
  let form,
    btn,
    statusDiv,
    progressBar,
    progressLabel,
    progressPopup,
    progressList,
    closeLogsBtn,
    toggleLogsBtn;

  // Initialize PDF tab
  function initPdfApp() {
    // Prevent multiple initializations of stats polling
    if (window.pdfStatsInterval) return;

    form = document.getElementById("uploadForm");
    btn = document.getElementById("compressBtn");
    statusDiv = document.getElementById("status");
    progressBar = document.getElementById("progressBar");
    progressLabel = document.getElementById("progressLabel");
    progressPopup = document.getElementById("progressPopup");
    progressList = document.getElementById("progressList");
    closeLogsBtn = document.getElementById("closeLogsBtn");
    toggleLogsBtn = document.getElementById("toggleLogsBtn");

    if (!form) return;

    // Toggle logs popup
    closeLogsBtn?.addEventListener("click", () => {
      progressPopup.classList.add("hidden");
      toggleLogsBtn?.classList.remove("hidden");
    });
    toggleLogsBtn?.addEventListener("click", () => {
      progressPopup.classList.remove("hidden");
      toggleLogsBtn?.classList.add("hidden");
    });

    // Form submit handler
    form.addEventListener("submit", uploadHandler);

    // Start stats polling (once per session)
    fetchStats();
    window.pdfStatsInterval = setInterval(fetchStats, 1000);
  }

  // Upload & compress handler
  async function uploadHandler(e) {
    e.preventDefault();
    const data = new FormData(form);
    const targetSizeInput = Number(
      document.getElementById("target_size").value
    );

    if (!targetSizeInput || targetSizeInput <= 0) {
      statusDiv.textContent = "Please enter a valid target size.";
      return;
    }

    data.append("target_size", targetSizeInput);

    // Reset UI
    btn.disabled = true;
    btn.textContent = "Uploading…";
    progressPopup?.classList.remove("hidden");
    toggleLogsBtn?.classList.add("hidden");
    progressList.innerHTML = "";
    progressBar.style.width = "0%";
    progressLabel.textContent = "Uploading…";
    statusDiv.textContent = "Uploading…";

    try {
      const res = await fetch("upload.php", { method: "POST", body: data });
      const json = await res.json();

      if (json.status === "ok") {
        progressBar.style.width = "100%";
        progressLabel.textContent = "Upload complete";
        statusDiv.textContent = "Upload complete";

        // Start polling progress
        pollStatus(json.job_id);

        // Temporarily disable form submit until download is done
        form.removeEventListener("submit", uploadHandler);
      } else {
        throw new Error(json.message);
      }
    } catch (err) {
      btn.disabled = false;
      btn.textContent = "Upload & Compress";
      statusDiv.textContent = "Error: " + err.message;
    }
  }

  // Poll compression progress
  async function pollStatus(jobId) {
    const interval = setInterval(async () => {
      try {
        const res = await fetch(`status.php?job_id=${jobId}`);
        const json = await res.json();

        // Update logs
        if (json.messages && Array.isArray(json.messages)) {
          progressList.innerHTML = "";
          json.messages.forEach((msg) => {
            const li = document.createElement("li");
            li.textContent = msg;
            progressList.appendChild(li);
          });
          progressList.scrollTop = progressList.scrollHeight;
        }

        // Handle status
        if (json.status === "compressing") {
          progressBar.style.width = json.progress + "%";
          progressLabel.textContent = `Compressing… ${json.progress}%`;
          statusDiv.textContent = `Compressing… ${json.progress}%`;
          btn.textContent = `Compressing… ${json.progress}%`;
        } else if (json.status === "done" || json.status === "warning") {
          clearInterval(interval);
          progressBar.style.width = "100%";
          progressLabel.textContent = "Compression complete";
          statusDiv.textContent =
            json.status === "warning"
              ? "Warning: " + json.message
              : "Compression completed!";
          btn.textContent = "Download";
          btn.disabled = false;

          // Temporary download handler
          const downloadHandler = () => {
            const a = document.createElement("a");
            a.href = `download.php?job_id=${jobId}`;
            a.download = "compressed.pdf";
            document.body.appendChild(a);
            a.click();
            a.remove();

            // Reset UI
            btn.textContent = "Upload & Compress";
            btn.disabled = false;
            form.querySelector('input[type="file"]').value = "";
            form.querySelector("#target_size").value = 200;
            progressBar.style.width = "0%";
            progressLabel.textContent = "Waiting for upload…";
            progressList.innerHTML = "";
            statusDiv.textContent = "";
            progressPopup?.classList.add("hidden");
            toggleLogsBtn?.classList.add("hidden");

            btn.removeEventListener("click", downloadHandler);
            form.addEventListener("submit", uploadHandler);
          };

          btn.addEventListener("click", downloadHandler, { once: true });
        } else if (json.status === "error") {
          clearInterval(interval);
          progressLabel.textContent = "Error";
          progressBar.style.width = "0%";
          statusDiv.textContent = "Error: " + json.message;
          btn.disabled = false;
          btn.textContent = "Upload & Compress";
        }
      } catch (err) {
        console.error("Polling failed:", err);
      }
    }, 500);
  }

  // Fetch PDF stats
  async function fetchStats() {
    try {
      const res = await fetch("stats.php");
      const json = await res.json();
      document.getElementById("totalConverted").textContent =
        json.total_converted;
      document.getElementById("todayConverted").textContent =
        json.today_converted;
      document.getElementById("queueCount").textContent = json.queue;
    } catch (err) {
      console.error("Failed to fetch stats:", err);
    }
  }

  // Expose initializer
  window.initPdfApp = initPdfApp;
})();
