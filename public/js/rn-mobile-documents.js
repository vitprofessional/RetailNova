(() => {
  const receipt = document.querySelector('[data-mobile-document-receipt]');
  const sheet = document.querySelector('[data-rn-document-sheet]');
  const printDocument = document.querySelector('[data-rn-print-document]');
  if (!printDocument) return;

  const paper = () => sheet?.querySelector('input[name="paper"]:checked')?.value
    || new URLSearchParams(window.location.search).get('size')
    || new URLSearchParams(window.location.search).get('printer')
    || printDocument.dataset.paper || 'a4';
  const applyPaper = () => {
    const kind = paper();
    const sizes = { a4: { size: 'A4 portrait', margin: 10, width: '190mm' }, thermal80: { size: '80mm', margin: 4, width: '72mm' }, thermal58: { size: '58mm', margin: 4, width: '48mm' } };
    const selected = sizes[kind] || sizes.a4;
    printDocument.style.width = document.body.hasAttribute('data-rn-document-preview') && kind === 'a4'
      ? 'min(190mm, calc(100vw - 32px))' : selected.width;
    document.body.dataset.rnPaper = kind;
    let pageSize = selected.size;
    if (kind !== 'a4') {
      const old = { display: printDocument.style.display, position: printDocument.style.position, visibility: printDocument.style.visibility, left: printDocument.style.left, top: printDocument.style.top };
      if (getComputedStyle(printDocument).display === 'none') Object.assign(printDocument.style, { display: 'block', position: 'absolute', visibility: 'hidden', left: '-100000px', top: '0' });
      const height = Math.max(40, Math.ceil(printDocument.getBoundingClientRect().height * 25.4 / 96));
      Object.assign(printDocument.style, old);
      pageSize = `${selected.size} ${height + selected.margin * 2}mm`;
    }
    let style = document.getElementById('rn-document-page-size');
    if (!style) { style = document.createElement('style'); style.id = 'rn-document-page-size'; document.head.append(style); }
    style.textContent = `@page { size: ${pageSize}; margin: ${selected.margin}mm; }`;
    printDocument.dataset.paper = kind;
    return kind;
  };
  const asciiPart = value => (value || '').normalize('NFKD').replace(/[^\x00-\x7f]/g, '')
    .replace(/[^a-zA-Z0-9._-]+/g, '-').replace(/^-+|-+$/g, '') || 'document';
  const documentNumber = printDocument.querySelector('.rn-print-document__title span')?.textContent?.trim();
  const fileBase = asciiPart(documentNumber || document.title);
  const prepared = new Map();
  let prepareSequence = 0;
  const shareButton = sheet?.querySelector('[data-document-share]');
  const shareFormat = () => sheet?.querySelector('input[name="share-format"]:checked')?.value || 'pdf';
  const scripts = [...document.scripts];
  const ownScript = scripts.find(script => script.src.includes('/js/rn-mobile-documents.js'));
  const pageBase = window.location.href.startsWith('http') ? window.location.href : 'http://localhost/';
  const scriptBase = ownScript?.src || new URL('/js/rn-mobile-documents.js', pageBase).href;
  const pdfjsUrl = new URL('../vendor/pdfjs/pdf.min.mjs', scriptBase).href;
  const pdfWorkerUrl = new URL('../vendor/pdfjs/pdf.worker.min.mjs', scriptBase).href;
  let pdfjsPromise;
  const getPdfjs = () => {
    if (window.RNPdfJs) return Promise.resolve(window.RNPdfJs);
    if (!pdfjsPromise) pdfjsPromise = import(pdfjsUrl).then(module => {
      module.GlobalWorkerOptions.workerSrc = pdfWorkerUrl;
      return module;
    });
    return pdfjsPromise;
  };
  const qrCanvas = async () => {
    const target = printDocument.querySelector('[data-document-qr]');
    const qr = window.RNQrCode;
    if (!target || target.dataset.ready === '1' || !qr?.toCanvas) return;
    const canvas = document.createElement('canvas');
    await qr.toCanvas(canvas, printDocument.dataset.qrPayload || 'RetailNova document', { width: 180, margin: 1, errorCorrectionLevel: 'M' });
    target.replaceChildren(canvas);
    target.dataset.ready = '1';
  };
  const pdfUrl = kind => {
    const direct = document.body.dataset.documentPdfUrl;
    if (direct) {
      const url = new URL(direct, window.location.href);
      url.searchParams.set('size', kind);
      return url;
    }
    const printUrl = receipt?.dataset.documentPrintUrl;
    if (!printUrl) throw new Error('PDF endpoint is missing for this document.');
    const url = new URL(printUrl, window.location.href);
    url.pathname = `${url.pathname.replace(/\/$/, '')}.pdf`;
    url.searchParams.set('size', kind);
    return url;
  };
  const fetchPdf = async kind => {
    const response = await fetch(pdfUrl(kind), { credentials: 'same-origin', headers: { Accept: 'application/pdf' } });
    if (!response.ok) throw new Error(`PDF request failed (${response.status})`);
    const blob = await response.blob();
    if (blob.type && !blob.type.includes('application/pdf')) throw new Error('The document service did not return a PDF.');
    return blob.type === 'application/pdf' ? blob : new Blob([blob], { type: 'application/pdf' });
  };
  const firstPagePng = async blob => {
    const pdfjs = await getPdfjs();
    const data = new Uint8Array(await blob.arrayBuffer());
    const loadingTask = pdfjs.getDocument({ data, useSystemFonts: true });
    const pdf = await loadingTask.promise;
    try {
      const page = await pdf.getPage(1);
      const viewport = page.getViewport({ scale: Math.max(3, 300 / 72) });
      const canvas = document.createElement('canvas');
      canvas.width = Math.ceil(viewport.width);
      canvas.height = Math.ceil(viewport.height);
      const context = canvas.getContext('2d', { alpha: false });
      context.fillStyle = '#fff';
      context.fillRect(0, 0, canvas.width, canvas.height);
      await page.render({ canvasContext: context, viewport, background: '#fff' }).promise;
      const png = await new Promise((resolve, reject) => canvas.toBlob(value => value ? resolve(value) : reject(new Error('PNG conversion failed')), 'image/png'));
      page.cleanup?.();
      return png;
    } finally { await pdf.destroy?.(); }
  };
  const download = (blob, filename) => {
    const url = URL.createObjectURL(blob);
    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = filename;
    anchor.click();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
  };
  const showSavedToast = () => {
    if (window.RNToast?.info) {
      window.RNToast.info(document.documentElement.lang.startsWith('bn') ? 'ফাইল সেভ হয়েছে — Files থেকে শেয়ার করুন' : 'File saved — share it from Files');
      return;
    }
    const toast = document.createElement('div');
    toast.className = 'rn-document-toast';
    toast.setAttribute('role', 'status');
    toast.textContent = receipt?.dataset.documentSavedText || 'File saved';
    document.body.append(toast);
    setTimeout(() => toast.remove(), 2800);
  };
  const prepareFiles = async () => {
    if (!sheet || !shareButton) return;
    const kind = applyPaper();
    const token = ++prepareSequence;
    shareButton.disabled = true;
    shareButton.setAttribute('aria-busy', 'true');
    shareButton.textContent = shareButton.dataset.buildingLabel;
    try {
      const pdfBlob = await fetchPdf(kind);
      const pngBlob = await firstPagePng(pdfBlob);
      const files = {
        png: new File([pngBlob], `${fileBase}.png`, { type: 'image/png' }),
        pdf: new File([pdfBlob], `${fileBase}.pdf`, { type: 'application/pdf' }),
      };
      if (token !== prepareSequence) return;
      prepared.set(kind, files);
      shareButton.disabled = false;
      shareButton.removeAttribute('aria-busy');
      shareButton.textContent = shareButton.dataset.readyLabel;
    } catch (error) {
      if (token !== prepareSequence) return;
      shareButton.disabled = true;
      shareButton.removeAttribute('aria-busy');
      shareButton.textContent = shareButton.dataset.buildingLabel;
      window.RNToast?.error(error?.message || (document.documentElement.lang.startsWith('bn') ? 'ফাইল তৈরি করা যায়নি' : 'Could not prepare document'));
      console.error('Document export preparation failed', error);
    }
  };
  const currentFiles = () => prepared.get(paper());
  const closeThen = action => { sheet.close(); action(); };
  const printInPlace = () => {
    const kind = applyPaper();
    if (receipt?.dataset.documentPrintUrl && !document.body.hasAttribute('data-rn-document-preview')) {
      const url = pdfUrl(kind);
      const opened = window.open(url.toString(), '_blank');
      if (opened) return;
    }
    qrCanvas().then(() => window.print()).catch(() => window.print());
  };
  sheet?.querySelector('[data-document-print]')?.addEventListener('click', () => closeThen(printInPlace));
  document.querySelector('.rn-document-toolbar [data-document-print]')?.addEventListener('click', printInPlace);
  document.querySelector('[data-document-back]')?.addEventListener('click', () => {
    if (window.history.length > 1) window.history.back();
    else window.location.assign(document.querySelector('[data-document-back]').dataset.backUrl);
  });
  document.querySelector('.rn-document-toolbar [data-document-pdf]')?.addEventListener('click', async () => {
    try { const kind = applyPaper(); download(await fetchPdf(kind), `${fileBase}.pdf`); }
    catch (error) { window.RNToast?.error(error?.message || 'PDF export failed'); }
  });
  sheet?.querySelector('[data-document-png]')?.addEventListener('click', () => {
    const file = currentFiles()?.png;
    if (file) { sheet.close(); download(file, file.name); showSavedToast(); }
    else prepareFiles();
  });
  sheet?.querySelector('[data-document-pdf]')?.addEventListener('click', () => {
    const file = currentFiles()?.pdf;
    if (file) { sheet.close(); download(file, file.name); }
    else prepareFiles();
  });
  sheet?.querySelectorAll('input[name="paper"]').forEach(input => input.addEventListener('change', prepareFiles));
  shareButton?.addEventListener('click', () => {
    const file = currentFiles()?.[shareFormat()];
    if (!file) { window.RNToast?.info(shareButton.dataset.buildingLabel); return; }
    sheet.close();
    const canShareFiles = typeof navigator.share === 'function' && typeof navigator.canShare === 'function' && navigator.canShare({ files: [file] });
    if (!canShareFiles) {
      download(file, file.name);
      showSavedToast();
      return;
    }
    let shareRequest;
    try { shareRequest = navigator.share({ files: [file], title: document.title }); }
    catch (error) { handleShareError(error); return; }
    Promise.resolve(shareRequest).catch(handleShareError);
  });
  function handleShareError(error) {
    if (error?.name === 'AbortError') return;
    if (error?.name === 'NotAllowedError') {
      window.RNToast?.info(document.documentElement.lang.startsWith('bn') ? 'আবার শেয়ার চাপুন' : 'Tap Share again');
      return;
    }
    window.RNToast?.error(error?.message || (document.documentElement.lang.startsWith('bn') ? 'ফাইল শেয়ার করা যায়নি' : 'Could not share file'));
  }
  const openShareSheet = () => {
    if (sheet && !sheet.open) sheet.showModal();
    prepareFiles();
  };
  document.querySelectorAll('[data-document-open-share], [data-receipt-action="print"], [data-receipt-action="pdf"], [data-receipt-action="share"]')
    .forEach(button => button.addEventListener('click', openShareSheet));
  applyPaper();
  qrCanvas().catch(error => console.warn('Document QR could not be rendered locally', error));
  if (new URLSearchParams(window.location.search).get('autoprint') === '1') setTimeout(printInPlace, 300);
})();
