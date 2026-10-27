(() => {
  const receipt = document.querySelector('[data-mobile-document-receipt]');
  const sheet = document.querySelector('[data-rn-document-sheet]');
  const printDocument = document.querySelector('[data-rn-print-document]');
  const tools = window.RNDocumentTools;
  if (!printDocument || !tools) return;
  let originalParent = null;
  let originalNextSibling = null;

  const paper = () => sheet?.querySelector('input[name="paper"]:checked')?.value
    || new URLSearchParams(window.location.search).get('size')
    || new URLSearchParams(window.location.search).get('printer')
    || printDocument.dataset.paper || 'a4';
  const applyPaper = () => {
    const kind = paper();
    const sizes = { a4: { size: 'A4 portrait', margin: 10, width: '190mm' }, thermal80: { size: '80mm', margin: 4, width: '72mm' }, thermal58: { size: '58mm', margin: 4, width: '48mm' } };
    const selected = sizes[kind] || sizes.a4;
    const { margin, width } = selected;
    printDocument.style.width = document.body.hasAttribute('data-rn-document-preview') && kind === 'a4'
      ? 'min(190mm, calc(100vw - 32px))' : width;
    document.body.dataset.rnPaper = kind;
    let pageSize = selected.size;
    if (kind !== 'a4') {
      const old = { display: printDocument.style.display, position: printDocument.style.position, visibility: printDocument.style.visibility, left: printDocument.style.left, top: printDocument.style.top };
      if (getComputedStyle(printDocument).display === 'none') Object.assign(printDocument.style, { display: 'block', position: 'absolute', visibility: 'hidden', left: '-100000px', top: '0' });
      const contentHeightMm = Math.max(40, Math.ceil(printDocument.getBoundingClientRect().height * 25.4 / 96));
      Object.assign(printDocument.style, old);
      pageSize = `${selected.size} ${contentHeightMm + margin * 2}mm`;
    }
    let style = document.getElementById('rn-document-page-size');
    if (!style) { style = document.createElement('style'); style.id = 'rn-document-page-size'; document.head.append(style); }
    style.textContent = `@page { size: ${pageSize}; margin: ${margin}mm; }`;
    printDocument.dataset.paper = kind;
    return kind;
  };
  const attachForPrint = () => {
    if (!originalParent) {
      originalParent = printDocument.parentNode;
      originalNextSibling = printDocument.nextSibling;
    }
    document.body.appendChild(printDocument);
  };
  const restoreAfterPrint = () => {
    if (!originalParent) return;
    originalParent.insertBefore(printDocument, originalNextSibling);
    originalParent = null;
    originalNextSibling = null;
  };
  const asciiPart = value => (value || '').normalize('NFKD').replace(/[^\x00-\x7f]/g, '')
    .replace(/[^a-zA-Z0-9._-]+/g, '-').replace(/^-+|-+$/g, '') || 'document';
  const businessName = printDocument.querySelector('.rn-print-document__brand strong')?.textContent?.trim();
  const documentNumber = printDocument.querySelector('.rn-print-document__title span')?.textContent?.trim();
  const fileBase = `${asciiPart(businessName)}-${asciiPart(documentNumber)}`;
  const prepared = new Map();
  let prepareSequence = 0;
  const shareButton = sheet?.querySelector('[data-document-share]');
  const shareFormat = () => sheet?.querySelector('input[name="share-format"]:checked')?.value || 'pdf';
  const qrCanvas = async () => {
    const target = printDocument.querySelector('[data-document-qr]');
    if (!target || target.dataset.ready === '1') return;
    const canvas = document.createElement('canvas');
    await tools.QRCode.toCanvas(canvas, printDocument.dataset.qrPayload || 'RetailNova document', { width: 180, margin: 1, errorCorrectionLevel: 'M' });
    target.replaceChildren(canvas);
    target.dataset.ready = '1';
  };
  const capture = async () => {
    const kind = applyPaper();
    await qrCanvas();
    if (document.fonts?.ready) await document.fonts.ready;
    const old = { display: printDocument.style.display, position: printDocument.style.position, left: printDocument.style.left, top: printDocument.style.top, zIndex: printDocument.style.zIndex };
    Object.assign(printDocument.style, { display: 'block', position: 'fixed', left: '0', top: '0', zIndex: '-1' });
    printDocument.classList.add('rn-document-capturing');
    const scale = Math.max(3, Number(window.devicePixelRatio) || 1);
    try { return await tools.html2canvas(printDocument, { scale, backgroundColor: '#fff', useCORS: true, logging: false }); }
    finally { printDocument.classList.remove('rn-document-capturing'); Object.assign(printDocument.style, old); }
  };
  const download = (blob, filename) => {
    const url = URL.createObjectURL(blob);
    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = filename;
    anchor.click();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
  };
  const canvasToBlob = (canvas, type) => new Promise((resolve, reject) => {
    canvas.toBlob(blob => blob ? resolve(blob) : reject(new Error('Could not create document image')), type);
  });
  const showSavedToast = () => {
    const toast = document.createElement('div');
    toast.className = 'rn-document-toast';
    toast.setAttribute('role', 'status');
    toast.textContent = receipt?.dataset.documentSavedText || 'File saved';
    document.body.append(toast);
    setTimeout(() => toast.remove(), 2800);
  };
  const createPdf = async (kind, canvas) => {
    const page = kind === 'a4'
      ? { width: 210, height: 297, contentWidth: 190, margin: 10 }
      : kind === 'thermal58'
        ? { width: 58, contentWidth: 48, margin: 4 }
        : { width: 80, contentWidth: 72, margin: 4 };
    const scale = page.contentWidth / canvas.width;
    if (kind !== 'a4') page.height = Math.ceil(canvas.height * scale + page.margin * 2);
    const sourcePageHeight = kind === 'a4' ? Math.max(1, Math.floor((page.height - page.margin * 2) / scale)) : canvas.height;
    const pdf = new tools.jsPDF({ orientation: 'portrait', unit: 'mm', format: [page.width, page.height], compress: true });
    for (let offset = 0, number = 0; offset < canvas.height; offset += sourcePageHeight, number++) {
      if (number) pdf.addPage([page.width, page.height], 'portrait');
      const slice = document.createElement('canvas');
      slice.width = canvas.width;
      slice.height = Math.min(sourcePageHeight, canvas.height - offset);
      slice.getContext('2d').drawImage(canvas, 0, offset, canvas.width, slice.height, 0, 0, slice.width, slice.height);
      pdf.addImage(slice.toDataURL('image/png'), 'PNG', page.margin, page.margin, page.contentWidth, slice.height * scale, undefined, 'SLOW');
    }
    return pdf;
  };
  const prepareFiles = async () => {
    if (!sheet || !shareButton) return;
    const kind = applyPaper();
    const token = ++prepareSequence;
    shareButton.disabled = true;
    shareButton.setAttribute('aria-busy', 'true');
    shareButton.textContent = shareButton.dataset.buildingLabel;
    try {
      const canvas = await capture();
      const png = await canvasToBlob(canvas, 'image/png');
      const pdf = await createPdf(kind, canvas);
      const files = {
        png: new File([png], `${fileBase}.png`, { type: 'image/png' }),
        pdf: new File([pdf.output('blob')], `${fileBase}.pdf`, { type: 'application/pdf' }),
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
      console.error(error);
    }
  };
  const currentFiles = () => prepared.get(paper());
  const closeThen = async action => { sheet.close(); await action(); };
  const printInPlace = async () => {
    const kind = applyPaper();
    if (receipt?.dataset.documentPrintUrl && !document.body.hasAttribute('data-rn-document-preview')) {
      const url = new URL(receipt.dataset.documentPrintUrl, window.location.href);
      url.searchParams.set('size', kind);
      url.searchParams.set('autoprint', '1');
      window.open(url.toString(), '_blank', 'noopener');
      return;
    }
    await qrCanvas();
    attachForPrint();
    window.print();
  };
  sheet?.querySelector('[data-document-print]')?.addEventListener('click', () => closeThen(printInPlace));
  document.querySelector('.rn-document-toolbar [data-document-print]')?.addEventListener('click', printInPlace);
  document.querySelector('[data-document-back]')?.addEventListener('click', () => {
    if (window.history.length > 1) window.history.back();
    else window.location.assign(document.querySelector('[data-document-back]').dataset.backUrl);
  });
  document.querySelector('.rn-document-toolbar [data-document-pdf]')?.addEventListener('click', async () => {
    try { const kind=applyPaper(); const canvas=await capture(); (await createPdf(kind,canvas)).save(`${fileBase}.pdf`); } catch (error) { console.error(error); }
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
      window.RNToast?.info(document.documentElement.lang.startsWith('bn') ? 'ফাইল সেভ হয়েছে — Files থেকে শেয়ার করুন' : 'File saved — share it from Files');
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
  const openShareSheet = () => { sheet?.showModal(); prepareFiles(); };
  document.querySelector('[data-document-open-share]')?.addEventListener('click', openShareSheet);
  window.addEventListener('afterprint', () => { restoreAfterPrint(); if (!document.body.hasAttribute('data-rn-document-preview')) delete document.body.dataset.rnPaper; });
  applyPaper();
  qrCanvas().catch(error => console.warn('Document QR could not be rendered locally', error));
  if (new URLSearchParams(window.location.search).get('autoprint') === '1') {
    setTimeout(printInPlace, 300);
  }
})();
