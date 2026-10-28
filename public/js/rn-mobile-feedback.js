(() => {
  'use strict';
  const root = document.querySelector('[data-rn-feedback]');
  if (!root) return;
  const banner = root.querySelector('[data-rn-error-banner]');
  const toast = root.querySelector('[data-rn-toast]');
  const toastMessage = root.querySelector('[data-rn-toast-message]');
  const undoButton = root.querySelector('[data-rn-toast-undo]');
  const errorMessage = root.querySelector('[data-rn-error-message]');
  const mobile = () => Boolean(document.querySelector('[data-rn-tabbar]'));
  let toastTimer = null;

  const showBanner = message => {
    errorMessage.textContent = message || root.dataset.labelError;
    banner.hidden = false;
    banner.scrollIntoView({ behavior: 'smooth', block: 'start' });
  };
  const showToast = (kind, message, duration = 2500) => {
    if (!message) return;
    if (kind === 'error') { showBanner(message); return; }
    clearTimeout(toastTimer);
    toast.classList.remove('is-success', 'is-info');
    toast.classList.add(kind === 'success' ? 'is-success' : 'is-info');
    toast.querySelector('.rn-m-toast-icon').textContent = kind === 'success' ? '✓' : 'i';
    toastMessage.textContent = message;
    if (undoButton) undoButton.hidden = true;
    toast.hidden = false;
    toast.style.zIndex = '12000';
    toastTimer = window.setTimeout(() => { toast.hidden = true; }, duration);
  };
  window.RNToast = {
    success: (message, options = {}) => showToast('success', message, Number(options.duration) || 2500),
    error: message => showToast('error', message || root.dataset.labelError),
    info: message => showToast('info', message),
    undo(message, callback) {
      showToast('info', message);
      if (!undoButton) return;
      undoButton.hidden = false;
      undoButton.onclick = () => { undoButton.hidden = true; clearTimeout(toastTimer); toast.hidden = true; callback?.(); };
    }
  };
  window.showToast = (title, message, icon) => {
    const kind = icon === 'error' ? 'error' : icon === 'success' ? 'success' : 'info';
    window.RNToast[kind](message || title || root.dataset.labelError);
  };

  // Native required validation runs before submit handlers, so surface it in
  // the same persistent mobile banner as server-side validation and retain the
  // user's current form values. Desktop keeps the browser's native behavior.
  document.addEventListener('invalid', event => {
    const field = event.target;
    if (!mobile() || !(field instanceof HTMLElement) || !field.validity?.valueMissing) return;
    event.preventDefault();
    const label = field.id
      ? document.querySelector(`label[for="${window.CSS?.escape ? CSS.escape(field.id) : field.id}"]`)
      : null;
    const labelText = (label?.textContent || field.getAttribute('aria-label') || '').replace(/[＊*]/g, '').trim();
    const action = field instanceof HTMLSelectElement ? 'বাছুন' : 'পূরণ করুন';
    const message = labelText ? `${labelText} ${action}` : 'এই ঘরটি পূরণ করুন';
    field.setAttribute('aria-invalid', 'true');
    const wrapper = field.closest('.rn-m-field,.form-group,.rn-customer-field') || field.parentElement;
    if (wrapper && !wrapper.querySelector('[data-rn-inline-error]')) {
      const line = document.createElement('p');
      line.className = 'rn-m-field-error';
      line.dataset.rnInlineError = field.name || field.id || 'required';
      line.textContent = message;
      field.insertAdjacentElement('afterend', line);
    }
    showBanner(message);
    requestAnimationFrame(() => {
      field.focus({ preventScroll: true });
      field.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
  }, true);

  if (toastMessage.textContent.trim()) {
    toast.classList.add('is-success');
    window.setTimeout(() => { toast.hidden = true; }, 2500);
  }

  function ensureConfirmSheet() {
    let sheet = document.querySelector('[data-rn-confirm-sheet]');
    if (sheet) return sheet;
    sheet = document.createElement('div');
    sheet.className = 'rn-m-confirm-sheet';
    sheet.dataset.rnConfirmSheet = '';
    sheet.hidden = true;
    sheet.innerHTML = '<button type="button" class="rn-m-confirm-scrim" data-rn-confirm-cancel tabindex="-1"></button><section class="rn-m-confirm-panel" role="dialog" aria-modal="true" aria-labelledby="rnConfirmTitle"><span class="rn-m-confirm-handle" aria-hidden="true"></span><h2 id="rnConfirmTitle"></h2><p data-rn-confirm-message></p><footer><button type="button" data-rn-confirm-cancel></button><button type="button" class="is-danger" data-rn-confirm-accept></button></footer></section>';
    document.body.append(sheet);
    return sheet;
  }
  window.RNConfirmSheet = {
    ask(message, labels = {}) {
      const sheet = ensureConfirmSheet();
      sheet.querySelector('h2').textContent = labels.title || root.dataset.labelConfirm;
      sheet.querySelector('[data-rn-confirm-message]').textContent = message || root.dataset.labelConfirm;
      sheet.querySelector('[data-rn-confirm-cancel]').textContent = labels.cancel || root.dataset.labelCancel;
      sheet.querySelector('[data-rn-confirm-accept]').textContent = labels.accept || root.dataset.labelDelete;
      sheet.hidden = false;
      document.body.classList.add('rn-m-confirm-open');
      return new Promise(resolve => {
        const finish = accepted => {
          sheet.hidden = true;
          document.body.classList.remove('rn-m-confirm-open');
          sheet.querySelectorAll('[data-rn-confirm-cancel],[data-rn-confirm-accept]').forEach(button => button.replaceWith(button.cloneNode(true)));
          resolve(accepted);
        };
        sheet.querySelectorAll('[data-rn-confirm-cancel]').forEach(button => button.addEventListener('click', () => finish(false), { once: true }));
        sheet.querySelector('[data-rn-confirm-accept]').addEventListener('click', () => finish(true), { once: true });
      });
    }
  };

  document.addEventListener('click', async event => {
    if (!mobile()) return;
    const target = event.target.closest('[data-confirm], [data-confirm-delete]');
    if (!target) return;
    if (target.dataset.rnConfirmPass === 'true') {
      delete target.dataset.rnConfirmPass;
      return;
    }
    const rawMessage = target.getAttribute('data-confirm');
    if (rawMessage === 'false' || rawMessage === '0') return;
    event.preventDefault();
    event.stopImmediatePropagation();
    const message = rawMessage && rawMessage !== 'delete' ? rawMessage : root.dataset.labelConfirm;
    if (!await window.RNConfirmSheet.ask(message)) return;
    if (target.tagName === 'A') {
      if (target.hasAttribute('data-confirm-delete')) {
        const form = document.createElement('form');
        form.method = 'POST'; form.action = target.href; form.hidden = true;
        form.innerHTML = '<input name="_token" value="' + (document.querySelector('meta[name=csrf-token]')?.content || '') + '"><input name="_method" value="DELETE">';
        document.body.append(form); form.submit();
      } else window.location.assign(target.href);
      return;
    }
    const form = target.closest('form');
    if (form) { target.dataset.rnConfirmPass = 'true'; form.requestSubmit(target); }
  }, true);

  root.addEventListener('click', event => {
    if (event.target.closest('[data-rn-error-close]')) banner.hidden = true;
  });

  function fieldFor(name) {
    const escaped = window.CSS?.escape ? CSS.escape(name) : name.replace(/[^\w-]/g, '\\$&');
    return document.querySelector(`[name="${escaped}"],#${escaped}`);
  }
  try {
    const errors = JSON.parse(root.querySelector('[data-rn-error-map]')?.textContent || '{}');
    let firstInvalid = null;
    Object.entries(errors).forEach(([name, messages]) => {
      const field = fieldFor(name);
      if (!field) return;
      firstInvalid ||= field;
      field.setAttribute('aria-invalid', 'true');
      const wrapper = field.closest('.rn-m-field,.form-group,.rn-customer-field') || field.parentElement;
      let line = Array.from(wrapper.querySelectorAll('[data-rn-inline-error]')).find(node => node.dataset.rnInlineError === name);
      if (!line) {
        line = document.createElement('p');
        line.className = 'rn-m-field-error';
        line.dataset.rnInlineError = name;
        field.insertAdjacentElement('afterend', line);
      }
      line.textContent = Array.isArray(messages) ? messages[0] : messages;
    });
    if (firstInvalid) requestAnimationFrame(() => firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' }));
  } catch (_) {}

  document.addEventListener('submit', event => {
    if (!mobile()) return;
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || form.method.toLowerCase() === 'get' || form.dataset.noSubmitLock === 'true' || form.dataset.rnSubmitting === 'true') return;
    form.dataset.rnSubmitting = 'true';
    form.querySelectorAll('button[type="submit"],button:not([type]),input[type="submit"]').forEach(button => {
      if (button.disabled) return;
      button.disabled = true;
      button.classList.add('is-submitting');
      const spinner = document.createElement('span'); spinner.className = 'rn-m-submit-spinner'; spinner.setAttribute('aria-hidden', 'true');
      button.append(spinner);
      button.setAttribute('aria-busy', 'true');
    });
  }, true);
  window.addEventListener('pageshow', () => {
    document.querySelectorAll('form[data-rn-submitting="true"]').forEach(form => {
      delete form.dataset.rnSubmitting;
      form.querySelectorAll('.is-submitting').forEach(button => { button.disabled = false; button.classList.remove('is-submitting'); button.removeAttribute('aria-busy'); button.querySelector('.rn-m-submit-spinner')?.remove(); });
    });
  });

  // Retire old status alerts from mobile layouts; validation fields stay mapped above.
  document.querySelectorAll('.rn-m-page .alert-success:not([data-rn-kept]),.rn-m-page .alert-danger:not(.alert-validation):not([data-rn-kept]),.rn-m-page .alert-warning:not([data-rn-kept])').forEach(element => {
    const message = element.textContent.trim();
    if (message && !element.querySelector('input,select,textarea')) showToast(element.classList.contains('alert-success') ? 'success' : element.classList.contains('alert-danger') ? 'error' : 'info', message);
    element.remove();
  });

  const deleteConfigElement = root.querySelector('[data-rn-delete-config]');
  if (deleteConfigElement) {
    try {
      const config = JSON.parse(deleteConfigElement.textContent);
      document.addEventListener('click', async event => {
        const target = event.target.closest('[data-confirm-delete]');
        if (!target || !mobile()) return;
        event.preventDefault();
        const accepted = await window.RNConfirmSheet.ask(config.text || root.dataset.labelConfirm, { title: config.title || root.dataset.labelConfirm, accept: root.dataset.labelDelete });
        if (accepted && target.href) window.location.assign(target.href);
      }, true);
    } catch (_) {}
  }
})();
