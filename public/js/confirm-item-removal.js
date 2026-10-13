/* One pending confirmation per row; cancellation never changes form state. */
(() => {
 const pending = new WeakSet();
 window.RNConfirmItemRemoval = async (row, remove) => {
  if (!row || !row.isConnected || pending.has(row)) return;
  pending.add(row);
  try {
   const t = key => window.RNTranslate('common.' + key);
   const accepted = window.Swal?.fire
    ? (await window.Swal.fire({title:t('remove_row'), text:t('confirm_remove_row'), icon:'warning', showCancelButton:true, confirmButtonText:t('remove'), cancelButtonText:t('cancel')})).isConfirmed === true
    : window.confirm(t('client_remove_this_row'));
   if (accepted && row.isConnected) remove();
  } finally { pending.delete(row); }
 };
})();
