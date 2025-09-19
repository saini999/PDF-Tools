# 📦 PDF Tools Web Application

A modern, user-friendly web suite for compressing PDFs, extracting passport photos, and cleaning up signature images.

---

## 📂 Directory Structure

```text
f:\GadSite\PDF Tools\
├── public/
│   ├── index.php                # Main entry, navigation, and section toggling
│   ├── pdf.php                  # PDF compression UI
│   ├── passport.php             # Passport photo UI
│   ├── signature.php            # Signature extraction UI
│   ├── pdf_process.php          # PDF compression backend
│   ├── passport_process.php     # Passport photo backend
│   ├── signature_process.php    # Signature extraction backend
│   ├── app.js                   # General JS (UI, AJAX, polling)
│   ├── passport.js              # Passport-specific JS
│   └── signature.js             # Signature-specific JS
├── workers/
│   ├── worker.php               # Ghostscript PDF worker (systemd service, multi-instance)
│   └── cleaner.php              # Cleans up temp files (cron job)
├── config.php                   # Configuration (paths, Redis, etc.)
├── worker.service               # Example systemd unit file for PDF worker
└── readme.md                    # This documentation
```

---

## ✨ Features

- **PDF Compression**: Upload PDFs or images (JPG, PNG, WebP), set a target size, and download a compressed PDF.
- **Passport Photo Tool**: Upload a photo, auto-detect face, crop, and compress to a target size. Optionally downscale (reduce resolution) instead of just compressing.
- **Signature Extractor**: Upload a signature image, select background color to remove, and compress the result.
- **Live Progress & Logs**: Real-time progress bars and logs for all tools.
- **Stats Dashboard**: See total processed, today's count, and queue size for each tool.
- **Modern UI**: Responsive, mobile-friendly, and easy to use.

---

## 🚦 How It Works

### User Flow

1. **Landing Page (`index.php`)**  
   Navigation bar lets users switch between PDF Compressor, Passport Photo, and Signature Extractor. Each tool loads dynamically.

2. **PDF Compressor (`pdf.php`)**  
   Upload one or more files (PDF or images), set a target size, and track progress. Download the compressed PDF when done.

3. **Passport Photo (`passport.php`)**  
   Upload a photo, auto-detect face, adjust crop, set target size, and optionally enable downscaling. Download the processed photo.

4. **Signature Extractor (`signature.php`)**  
   Upload a signature image, select background color to remove, set target file size, and download the cleaned signature.

---

## 🛠️ Backend Overview

- **PHP**: Handles all backend processing.
- **Redis**: Tracks jobs, progress, and statistics.
- **Imagick**: Image processing (resize, compress, background removal).
- **Ghostscript**: PDF compression via command-line.
- **Workers**: Background scripts for job processing and cleanup.

---

## 🗂️ File & Service Details

### Workers

- **`workers/worker.php`**  
  Ghostscript worker for PDF compression. Pulls jobs from Redis and processes them.  
  _Run as a systemd service; supports multiple instances (one per CPU core)._

- **`workers/cleaner.php`**  
  Run via cron every minute to clean up temporary files (uploads, processed files).  
  _Skips deleting jobs/files newer than 5 minutes to avoid interfering with active jobs._

### Systemd Worker Service Setup

To run the PDF compression worker as a background service (and scale to multiple CPU cores):

1. **Edit the Service File**  
   Copy `worker.service` to your systemd directory (usually `/etc/systemd/system/`).  
   Edit the file and replace `/path/to/site/public_html/` with the actual path to your site's `public_html` directory.

2. **Rename for Multiple Instances**  
   Rename the file to `worker@.service` (note the `@`), which allows running multiple instances:
   ```sh
   sudo mv /etc/systemd/system/worker.service /etc/systemd/system/worker@.service
   ```

3. **Enable and Start Instances**  
   Enable and start as many instances as you want (typically one per CPU core). For example, to run 4 workers:
   ```sh
   sudo systemctl daemon-reload
   sudo systemctl enable --now worker@1.service
   sudo systemctl enable --now worker@2.service
   sudo systemctl enable --now worker@3.service
   sudo systemctl enable --now worker@4.service
   ```
   Each instance will run independently and process jobs from the Redis queue.

4. **Check Status**  
   ```sh
   sudo systemctl status worker@1.service
   ```

### Cron Setup for Cleaner

To run the cleaner every minute, add this to your crontab:
```cron
* * * * * php /path/to/site/public_html/workers/cleaner.php
```
Replace `/path/to/site/public_html/` with your actual path.

---

## ⚙️ Customization

- **Target sizes**: User-configurable in the form.
- **Downscaling**: Toggle for passport photos (button with check/X and color change).
- **Stats**: Real-time stats for each tool.
- **Easy Theming**: Uses Tailwind CSS for rapid UI changes.

---

## 📋 Requirements

- PHP 7.4+
- Imagick PHP extension
- Redis server and PHP Redis extension
- Ghostscript (for PDF compression)
- Cropper.js (for frontend cropping)

---

## 🚀 Deployment

1. Place the code in your web server's document root.
2. Configure `config.php` for your environment (paths, Redis, etc.).
3. Ensure PHP, Imagick, Redis, and Ghostscript are installed and accessible.
4. (Optional) Set up systemd workers and cron cleaner as described above.
5. Access the site via your browser.

---

## 🙋 Support

For any issues or queries, Discord: **n.o.o.o.r**

---
