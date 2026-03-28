import * as pdfjsLib from 'https://cdn.jsdelivr.net/npm/pdfjs-dist@4.4.168/build/pdf.min.mjs';

pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@4.4.168/build/pdf.worker.min.mjs';

function initReaderPage() {
  const configEl = document.getElementById('reader-config');
  if (!configEl) {
    return;
  }

  const pdfUrl = configEl.dataset.pdfUrl || '';
  const bookId = Number(configEl.dataset.bookId || '0');
  const csrfToken = configEl.dataset.csrfToken || '';
  const totalBookPages = Number(configEl.dataset.totalBookPages || '0');
  let currentPage = Number(configEl.dataset.initialPage || '1');

  const canvas = document.getElementById('pdf-canvas');
  const canvasWrap = document.querySelector('.reader-canvas-wrap');
  const toolbar = document.querySelector('.reader-toolbar');
  const ctx = canvas ? canvas.getContext('2d') : null;
  const pageIndicator = document.getElementById('page-indicator');
  const statusEl = document.getElementById('reader-status');
  const errorEl = document.getElementById('reader-error');
  const fallbackWrap = document.getElementById('reader-fallback');
  const fallbackFrame = document.getElementById('reader-fallback-frame');
  const prevBtn = document.getElementById('prev-page');
  const nextBtn = document.getElementById('next-page');

  if (!canvas || !ctx || !pageIndicator || !statusEl || !prevBtn || !nextBtn || !pdfUrl) {
    return;
  }

  let pdfDoc = null;
  let rendering = false;
  let pendingPage = null;
  let lastSavedPage = 0;
  let saveTimer = null;
  let advancedMode = false;

  function activateAdvancedReader() {
    if (advancedMode) {
      return;
    }

    advancedMode = true;

    if (toolbar) {
      toolbar.style.display = 'flex';
    }

    if (canvasWrap) {
      canvasWrap.style.display = 'block';
    }

    if (fallbackWrap) {
      fallbackWrap.style.display = 'none';
    }

    if (errorEl) {
      errorEl.style.display = 'none';
      errorEl.textContent = '';
    }

    if (canvas) {
      canvas.style.display = 'block';
    }
  }

  function showFallback(message) {
    if (errorEl) {
      errorEl.style.display = 'block';
      errorEl.textContent = message;
    }

    if (fallbackWrap) {
      fallbackWrap.style.display = 'block';
    }

    if (fallbackFrame && !fallbackFrame.src) {
      fallbackFrame.src = pdfUrl;
    }

    if (canvasWrap) {
      canvasWrap.style.display = 'none';
    }

    if (toolbar) {
      toolbar.style.display = 'none';
    }

    if (canvas) {
      canvas.style.display = 'none';
    }

    if (prevBtn) {
      prevBtn.disabled = true;
    }

    if (nextBtn) {
      nextBtn.disabled = true;
    }
  }

  function clampPage(page, maxPage) {
    if (page < 1) {
      return 1;
    }
    if (page > maxPage) {
      return maxPage;
    }
    return page;
  }

  async function saveProgress(pageNumber) {
    if (totalBookPages <= 0 || bookId <= 0 || csrfToken === '') {
      return;
    }

    const pagesLues = Math.min(pageNumber, totalBookPages);
    if (pagesLues === lastSavedPage) {
      return;
    }

    const form = new FormData();
    form.append('csrf_token', csrfToken);
    form.append('book_id', String(bookId));
    form.append('pages_lues', String(pagesLues));

    try {
      const response = await fetch('/club-lecture/pages/livres/progression_auto.php', {
        method: 'POST',
        body: form,
        credentials: 'same-origin'
      });

      if (!response.ok) {
        return;
      }

      const data = await response.json();
      if (data && data.ok) {
        lastSavedPage = pagesLues;
        statusEl.textContent = 'Progression enregistree automatiquement: ' + data.pourcentage + '%';
      }
    } catch (e) {
      // La lecture reste utilisable meme si la sauvegarde echoue.
    }
  }

  async function renderPage(pageNumber) {
    rendering = true;

    try {
      const page = await pdfDoc.getPage(pageNumber);
      const viewport = page.getViewport({ scale: 1.4 });
      canvas.width = viewport.width;
      canvas.height = viewport.height;

      await page.render({ canvasContext: ctx, viewport }).promise;

      activateAdvancedReader();

      pageIndicator.textContent = 'Page ' + pageNumber + ' / ' + pdfDoc.numPages;
      prevBtn.disabled = pageNumber <= 1;
      nextBtn.disabled = pageNumber >= pdfDoc.numPages;

      rendering = false;

      if (pendingPage !== null) {
        const nextPending = pendingPage;
        pendingPage = null;
        queueRenderPage(nextPending);
        return;
      }

      if (saveTimer) {
        clearTimeout(saveTimer);
      }

      saveTimer = setTimeout(function () {
        saveProgress(pageNumber);
      }, 250);
    } catch (e) {
      rendering = false;
      showFallback('Impossible d\'afficher ce PDF avec le lecteur avance.');
    }
  }

  function queueRenderPage(pageNumber) {
    if (rendering) {
      pendingPage = pageNumber;
      return;
    }
    renderPage(pageNumber);
  }

  prevBtn.addEventListener('click', function () {
    currentPage = clampPage(currentPage - 1, pdfDoc.numPages);
    queueRenderPage(currentPage);
  });

  nextBtn.addEventListener('click', function () {
    currentPage = clampPage(currentPage + 1, pdfDoc.numPages);
    queueRenderPage(currentPage);
  });

  (async function () {
    try {
      const fileResponse = await fetch(pdfUrl, {
        credentials: 'same-origin'
      });

      if (!fileResponse.ok) {
        throw new Error('HTTP ' + fileResponse.status);
      }

      const fileBuffer = await fileResponse.arrayBuffer();
      const loadingTask = pdfjsLib.getDocument({ data: fileBuffer });
      pdfDoc = await loadingTask.promise;
      currentPage = clampPage(currentPage, pdfDoc.numPages);
      queueRenderPage(currentPage);
    } catch (e) {
      showFallback('Le chargement du document a echoue dans le lecteur avance.');
    }
  })();
}

initReaderPage();

