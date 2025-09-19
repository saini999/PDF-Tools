# PDF Tools Web Application

This web application provides three main tools:
- **PDF Compressor**: Compress PDF and image files to a target size.
- **Passport Photo Extractor**: Crop, resize, and compress passport photos with optional downscaling.
- **Signature Extractor**: Remove backgrounds from signature images and compress to a target size.

## Features

- **PDF Compression**: Upload PDFs or images (JPG, PNG, WebP), set a target size, and download a compressed PDF.
- **Passport Photo Tool**: Upload a photo, auto-detect face, crop, and compress to a target size. Optionally downscale (reduce resolution) instead of just compressing.
- **Signature Extractor**: Upload a signature image, select background color to remove, and compress the result.

## How It Works

### User Flow

1. **Landing Page (`index.php`)**:  
   - Navigation bar lets users switch between PDF Compressor, Passport Photo, and Signature Extractor.
   - Each tool is loaded dynamically without reloading the page.

2. **PDF Compressor (`pdf.php`)**:  
   - Users upload one or more files (PDF or images).
   - Set a target size in KB.
   - Progress bar and logs show compression progress.
   - Download link provided after processing.

3. **Passport Photo (`passport.php`)**:  
   - Upload a photo (JPG/PNG).
   - Face is auto-detected and crop area is shown.
   - User can adjust crop.
   - Set target size and optionally enable downscaling (button toggles between compression and downscaling).
   - Download processed photo.

4. **Signature Extractor (`signature.php`)**:  
   - Upload a signature image.
   - Select background color to remove.
   - Set target file size.
   - Download cleaned signature.

### Backend

- **PHP** is used for all backend processing.
- **Redis** is used for job tracking, progress, and statistics.
- **Imagick** is used for image processing (resize, compress, background removal).
- **PDF processing** uses command-line tools (e.g., Ghostscript) and PHP libraries.

### File Structure

- `public/index.php`: Main entry point, navigation, and section toggling.
- `public/pdf.php`: PDF compression form and UI.
- `public/passport.php`: Passport photo form and UI.
- `public/signature.php`: Signature extraction form and UI.
- `public/passport_process.php`: Handles passport photo processing (crop, resize, compress, downscale).
- `public/pdf_process.php`: Handles PDF compression.
- `public/signature_process.php`: Handles signature extraction and background removal.
- `public/app.js`, `passport.js`, `signature.js`: JavaScript for UI, AJAX, and polling.
- `config.php`: Configuration (paths, Redis, etc.).
- **`workers/` directory**:
  - `worker.php`: Runs the Ghostscript worker for PDF compression. It pulls jobs from Redis and processes them. This script is intended to be run as a systemd service and can be started as multiple instances (one per CPU core) for parallel processing.
  - `cleaner.php`: Run via cron every minute to clean up temporary files (uploads, processed files). It skips deleting jobs/files that are newer than 5 minutes to avoid interfering with active jobs.

### How the Code Works

- **Forms** use AJAX to submit files and parameters to backend PHP scripts.
- **Progress** is tracked in Redis and polled by the frontend to update progress bars and logs.
- **Image Processing**:
  - For passport photos, the backend can either compress or downscale images based on user selection.
  - For signatures, background color is removed and the image is compressed.
- **PDF Processing**:
  - Images are combined into a PDF if multiple images are uploaded.
  - Compression is performed to meet the target size.

### Customization

- **Target sizes** can be changed by the user in the form.
- **Downscaling** for passport photos can be toggled with a button (shows check/X and changes color).
- **Stats** are shown for each tool (total processed, today, queue).

## Requirements

- PHP 7.4+
- Imagick PHP extension
- Redis server and PHP Redis extension
- Ghostscript (for PDF compression)
- Cropper.js (for frontend cropping)

## Deployment

1. Place the code in your web server's document root.
2. Configure `config.php` for your environment (paths, Redis, etc.).
3. Ensure PHP, Imagick, Redis, and Ghostscript are installed and accessible.
4. Access the site via your browser.

---

For any issues or queries, Discord: n.o.o.o.r
