(() => {
  const receipt = document.querySelector('[data-mobile-document-receipt]');
  const sheet = document.querySelector('[data-rn-document-sheet]');
  const printDocument = document.querySelector('[data-rn-print-document]');
  const tools = window.RNDocumentTools;
  if (!printDocument || !tools) return;
  let originalParent = null;
  let originalNextSibling = null;

  const paper = () => new URLSearchParams(window.location.search).get('size')
    || new URLSearchParams(window.location.search).get('printer')
    || sheet?.querySelector('input[name="paper"]:checked')?.value
    || printDocument.dataset.paper || 'a4';
  const applyPaper = () => {
    const kind = paper();
    const sizes = { a4: ['A4 portrait', '10mm', '190mm'], thermal80: ['80mm 200mm', '4mm', '72mm'], thermal58: ['58mm 200mm', '4mm', '48mm'] };
    const [size, margin, width] = sizes[kind] || sizes.a4;
    let style = document.getElementById('rn-document-page-size');
    if (!style) { style = document.createElement('style'); style.id = 'rn-document-page-size'; document.head.append(style); }
    style.textContent = `@page { size: ${size}; margin: ${margin}; }`;
    printDocument.dataset.paper = kind;
    printDocument.style.width = width;
    document.body.dataset.rnPaper = kind;
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
    await document.fonts?.ready;
    const old = { display: printDocument.style.display, position: printDocument.style.position, left: printDocument.style.left, top: printDocument.style.top, zIndex: printDocument.style.zIndex };
    Object.assign(printDocument.style, { display: 'block', position: 'fixed', left: '0', top: '0', zIndex: '-1' });
    const width = printDocument.getBoundingClientRect().width || 1;
    const scale = kind === 'thermal80' ? 576 / width : kind === 'thermal58' ? 384 / width : 2;
    try { return await tools.html2canvas(printDocument, { scale, backgroundColor: '#ffffff', useCORS: true, logging: false }); }
    finally { Object.assign(printDocument.style, old); }
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
  const createPdf = async () => {
    const kind = applyPaper();
    const canvas = await capture();
    const page = kind === 'a4'
      ? { width: 210, height: 297, contentWidth: 190, margin: 10 }
      : kind === 'thermal58'
        ? { width: 58, height: 200, contentWidth: 48, margin: 4 }
        : { width: 80, height: 200, contentWidth: 72, margin: 4 };
    const scale = page.contentWidth / canvas.width;
    const sourcePageHeight = Math.max(1, Math.floor((page.height - page.margin * 2) / scale));
    const pdf = new tools.jsPDF({ orientation: 'portrait', unit: 'mm', format: [page.width, page.height], compress: true });
    for (let offset = 0, number = 0; offset < canvas.height; offset += sourcePageHeight, number++) {
      if (number) pdf.addPage([page.width, page.height], 'portrait');
      const slice = document.createElement('canvas');
      slice.width = canvas.width;
      slice.height = Math.min(sourcePageHeight, canvas.height - offset);
      slice.getContext('2d').drawImage(canvas, 0, offset, canvas.width, slice.height, 0, 0, slice.width, slice.height);
      pdf.addImage(slice.toDataURL('image/jpeg', 0.88), 'JPEG', page.margin, page.margin, page.contentWidth, slice.height * scale, undefined, 'FAST');
    }
    return pdf;
  };
  const closeThen = async action => { sheet.close(); await action(); };
  sheet?.querySelector('[data-document-print]')?.addEventListener('click', () => closeThen(async () => {
    const kind = applyPaper();
    const printUrl = receipt.dataset.documentPrintUrl;
    if (printUrl) {
      const url = new URL(printUrl, window.location.href);
      url.searchParams.set('size', kind);
      url.searchParams.set('autoprint', '1');
      window.open(url.toString(), '_blank', 'noopener');
      return;
    }
    await qrCanvas();
    attachForPrint();
    window.print();
  }));
  sheet?.querySelector('[data-document-png]')?.addEventListener('click', () => closeThen(async () => {
    const blob = await canvasToBlob(await capture(), 'image/png');
    download(blob, `${fileBase}.png`);
    showSavedToast();
  }));
  sheet?.querySelector('[data-document-pdf]')?.addEventListener('click', () => closeThen(async () => (await createPdf()).save(`${fileBase}.pdf`)));
  sheet?.querySelector('[data-document-share]')?.addEventListener('click', () => closeThen(async () => {
    const pdf = await createPdf();
    const file = new File([pdf.output('blob')], `${fileBase}.pdf`, { type: 'application/pdf' });
    if (navigator.canShare?.({ files: [file] })) await navigator.share({ files: [file], title: document.title });
    else { download(file, file.name); showSavedToast(); }
  }));
  window.addEventListener('afterprint', () => { restoreAfterPrint(); delete document.body.dataset.rnPaper; });
  if (new URLSearchParams(window.location.search).get('autoprint') === '1') {
    setTimeout(async () => { await qrCanvas(); applyPaper(); attachForPrint(); window.print(); }, 300);
  }
})();
