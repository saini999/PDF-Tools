<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tools Gaditc</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" type="image/png" href="/pdf.png">

</head>

<body class="bg-gray-100 font-sans min-h-screen flex flex-col">

  <!-- Top Navbar -->
  <nav class="bg-indigo-600 text-white shadow-md">
    <div class="max-w-6xl mx-auto px-4">
      <div class="flex justify-between h-16 items-center">
        <div class="text-xl font-bold">ITI WebTools</div>
        <div class="flex space-x-0 border border-indigo-500 rounded-lg overflow-hidden">
          <!-- Active tab -->
          <button id="navPdf"
            class="px-4 py-2 font-medium transition 
                 bg-indigo-700 border-r border-indigo-500 
                 shadow-inner">
            📄 PDF Compress
          </button>
          <!-- Inactive tab -->
          <button id="navPassport"
            class="px-4 py-2 font-medium transition 
                 hover:bg-indigo-500 border-l border-indigo-500">
            🪪 Passport Photo
          </button>
          <button id="navSignature" class="px-4 py-2 font-medium transition hover:bg-indigo-500 border-l border-indigo-500"> &#9997; Signature Extractor</button>
        </div>
      </div>
    </div>
  </nav>


  <main class="flex-1">
    <!-- Main content container -->
    <div class="max-w-4xl mx-auto p-6">

      <!-- PDF Compress Section -->
      <div id="pdfSection">
        <?php include 'pdf.php'; ?>
      </div>

      <!-- Passport Photo Section -->
      <div id="passportSection" class="hidden">
        <?php include 'passport.php'; ?>
      </div>
      <!-- Signature Extractor Section -->
      <div id="signatureSection" class="hidden"> 
        <?php include 'signature.php'; ?> 
      </div>

    </div>

  </main>

  <script src="app.js?v=103"></script>
  <script src="passport.js?v=103"></script>
  <script src="signature.js?v=103"></script>

  <!-- Script to toggle sections -->
  <script>
    const navPdf = document.getElementById('navPdf');
    const navPassport = document.getElementById('navPassport');
    const pdfSection = document.getElementById('pdfSection');
    const passportSection = document.getElementById('passportSection');
    const navSignature = document.getElementById('navSignature');
    const signatureSection = document.getElementById('signatureSection');

    navPdf.addEventListener('click', () => {
      pdfSection.classList.remove('hidden');
      passportSection.classList.add('hidden');
      signatureSection.classList.add('hidden');

      // Start PDF polling
      if (window.initPdfApp) window.initPdfApp();

      // Stop Passport polling
      if (window.passportStatsInterval) {
        clearInterval(window.passportStatsInterval);
        window.passportStatsInterval = null;
      }
    });

    navPassport.addEventListener('click', () => {
      passportSection.classList.remove('hidden');
      pdfSection.classList.add('hidden');
      signatureSection.classList.add('hidden');

      // Start Passport polling
      if (window.initPassportTab) window.initPassportTab();

      // Stop PDF polling
      if (window.pdfStatsInterval) {
        clearInterval(window.pdfStatsInterval);
        window.pdfStatsInterval = null;
      }
    });

    navSignature.addEventListener('click', () => {
      pdfSection.classList.add('hidden');
      passportSection.classList.add('hidden');
      signatureSection.classList.remove('hidden');
      if (window.initSignatureTab) window.initSignatureTab();
      if (window.passportStatsInterval) {
        clearInterval(window.passportStatsInterval);
        window.passportStatsInterval = null;
      }
      if (window.pdfStatsInterval) {
        clearInterval(window.pdfStatsInterval);
        window.pdfStatsInterval = null;
      }
    });

    // Initialize default tab
    navPdf.click();

    // Optional: highlight active tab
    const tabs = [navPdf, navPassport];
    tabs.forEach(tab => tab.addEventListener('click', () => {
      tabs.forEach(t => t.classList.remove('bg-indigo-700'));
      tab.classList.add('bg-indigo-700');
    }));

    // Set default active tab
    navPdf.classList.add('bg-indigo-700');
  </script>

  <!-- Include passport.js after the sections -->


  <footer class="bg-slate-800 text-gray-200 py-4 text-center text-sm shadow-inner">
    This website is developed & managed by
    <a href="https://md.noorautomation.in" target="_blank" class="text-indigo-400 hover:text-indigo-200 font-medium">
      Harnoor Saini
    </a>
    @
    <a href="https://gaditc.in" target="_blank" class="text-indigo-400 hover:text-indigo-200 font-medium">
      Guru Arjun Dev ITC.
    </a>
    <br> For any queries or issues related to site please contact: 70097-08849
  </footer>




</body>

</html>