<script>
(function() {
    // This script is now confirmed to be running.

    console.log("Bulk actions script loaded and executing.");

    // --- Main function to run after the DOM is ready ---
    function initializeBulkActions() {
        console.log("DOM is ready, initializing bulk actions.");

        // --- DOM Elements ---
        var wrapper = document.getElementById('bulkActionsWrapper');
        var countEl = document.getElementById('bulkSelectedCount');
        var deleteBtn = document.getElementById('bulkDeleteBtn');
        var printBtn = document.getElementById('bulkPrintBtn');
        var pdfBtn = document.getElementById('bulkPdfBtn');
        var deleteForm = document.getElementById('bulkDeleteForm');
        var printForm = document.getElementById('bulkPrintForm');
        var pdfForm = document.getElementById('bulkPdfForm');
        var allCheckboxes = document.querySelectorAll('.bulk-select');

        // Dynamically find the "Select All" checkbox on the page and assign it the correct class for our script to use.
        var masterSelectors = ['#selectAllProducts','#selectAllBrands','#selectAllCategories','#selectAllUnits','#selectAllSales','#selectAllDamage','#selectAllProvidedServices','#selectAllCustomers','#selectAllSuppliers'];
        var selectAllCheckbox = null;
        masterSelectors.forEach(function(sel){
            var el = document.querySelector(sel);
            if(el){
                el.classList.add('bulk-select-all');
                selectAllCheckbox = el;
                console.log("Found and initialized 'Select All' checkbox:", selectAllCheckbox);
            }
        });

        if (!wrapper) {
            // Mobile cards do not render the desktop bulk-action toolbar or checkboxes.
            return;
        }
        console.log("Found UI wrapper:", wrapper);
        if (allCheckboxes.length === 0) {
            console.warn("Warning: No '.bulk-select' checkboxes found on this page.");
        }

        // --- UI Update Logic ---
        function updateBulkUI() {
            var selected = document.querySelectorAll('.bulk-select:checked');
            var count = selected.length;

            console.log(count + " items selected.");

            if (countEl) {
                countEl.textContent = count;
            }

            var hasSelection = count > 0;
            wrapper.classList.toggle('d-none', !hasSelection);
            if (deleteBtn) deleteBtn.disabled = !hasSelection;

            // Determine whether selected rows belong to the same customer and same date
            var groupable = false;
            if (hasSelection) {
                var firstCust = null, firstDate = null, allSame = true;
                selected.forEach(function(cb, idx){
                    var tr = cb.closest('tr');
                    if (!tr) return;
                    var date = tr.dataset.date || (tr.getAttribute('data-date')) || '';
                    // try to read customer from data attribute, otherwise second cell text
                    var cust = tr.dataset.customer || (tr.getAttribute('data-customer')) || '';
                    if(!cust){
                        var tds = tr.querySelectorAll('td');
                        if(tds.length > 1) cust = tds[1].textContent.trim();
                    }
                    if (idx === 0) {
                        firstCust = cust;
                        firstDate = date;
                    } else {
                        if ((cust || '') !== (firstCust || '') || (date || '') !== (firstDate || '')) {
                            allSame = false;
                        }
                    }
                });
                groupable = allSame;
            }

            // show a groupability hint and enable/disable print button accordingly
            var hint = document.getElementById('bulkGroupHint');
            if (!groupable && hasSelection) {
                if (hint) hint.classList.remove('d-none');
            } else {
                if (hint) hint.classList.add('d-none');
            }
            if (printBtn) printBtn.disabled = !groupable;
            if (pdfBtn) pdfBtn.disabled = !groupable;
        }

        // --- Event Listeners ---
        document.addEventListener('change', function(e) {
            var target = e.target;
            if (target.matches('.bulk-select-all')) {
                console.log("'Select All' checkbox changed. New state: " + target.checked);
                allCheckboxes.forEach(function(cb) {
                    cb.checked = target.checked;
                });
                updateBulkUI();
            } else if (target.matches('.bulk-select')) {
                console.log("An individual checkbox was changed.");
                updateBulkUI();
            }
        });

        // --- Form Submission Logic ---
        function handleFormSubmit(event) {
            event.preventDefault(); // ALWAYS stop the form's default submission
            var form = event.currentTarget;
            var entity = form.getAttribute('data-entity') || 'items';
            console.log("SUBMIT EVENT INTERCEPTED for form:", form.id);

            var selectedIds = Array.from(document.querySelectorAll('.bulk-select:checked')).map(function(cb) {
                return cb.value;
            });

            if (selectedIds.length === 0) {
                console.warn("Submission blocked: No items were selected.");
                alert(form.id.includes('Print')
                    ? window.RNTranslate('common.client_please_select_at_least_one_item_to_print')
                    : window.RNTranslate('common.client_please_select_at_least_one_item_to_delete'));
                return;
            }

            console.log("Found " + selectedIds.length + " selected IDs:", selectedIds);

            // Specific confirmation for delete action
            if (form.id === 'bulkDeleteForm') {
                event.preventDefault();
                // Cancelling sales needs a reason instead of the profile/full-delete choice.
                if (form.hasAttribute('data-bulk-reason') && typeof window.rnAskSaleCancelReason === 'function') {
                    window.rnAskSaleCancelReason(true).then(function (reason) {
                        if (!reason) { return; }
                        form.querySelector('input[name="reason"]').value = reason;
                        submitBulkDelete(selectedIds, form, 'cancel');
                    });
                    return;
                }
                console.log("Delete form detected, showing bulk delete modal");
                showBulkDeleteModal(selectedIds, entity, form);
                return;
            }

            // Clear any old hidden inputs from previous attempts
            form.querySelectorAll('input[name="selected[]"]').forEach(function(inp) {
                inp.remove();
            });
            console.log("Cleared old hidden 'selected[]' inputs from the form.");

            // Add current IDs as new hidden inputs
            selectedIds.forEach(function(id) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected[]';
                input.value = id;
                form.appendChild(input);
            });
            console.log("Appended " + selectedIds.length + " new hidden inputs to the form.");

            // For print forms, open a named window and submit into it (more reliable than bare target _blank)
            if (form.id === 'bulkPrintForm' || form.id === 'bulkPdfForm') {
                try {
                    var winName = 'rn_bulk_print_' + Date.now();
                    var newWin = window.open('', winName);
                    if (newWin) {
                        form.target = winName;
                        // Use a short delay to ensure target is applied in all browsers
                        setTimeout(function(){
                            form.submit();
                            // focus the new window/tab
                            try{ newWin.focus(); }catch(e){}
                        }, 50);
                        console.log('Submitted print form into named window:', winName);
                        return;
                    }
                } catch (e) {
                    console.warn('Named window open failed, falling back to direct submit', e);
                }
            }

            // Default submit for other forms
            console.log("Submitting the form to " + form.action);
            form.submit();
        }

        if (deleteForm) {
            console.log("Attaching submit listener to the delete form.");
            deleteForm.addEventListener('submit', handleFormSubmit);
        } else {
            console.warn("Delete form ('bulkDeleteForm') not found.");
        }

        if (printForm) {
            console.log("Attaching submit listener to the print form.");
            printForm.addEventListener('submit', function(e){
                // re-check groupable condition before submitting
                var selected = document.querySelectorAll('.bulk-select:checked');
                if(selected.length === 0){ e.preventDefault(); alert(window.RNTranslate('common.client_please_select_at_least_one_item_to_print')); return; }
                var firstCust = null, firstDate = null, allSame = true;
                selected.forEach(function(cb, idx){
                    var tr = cb.closest('tr');
                    var date = tr ? (tr.dataset.date || tr.getAttribute('data-date') || '') : '';
                    var cust = tr ? (tr.dataset.customer || tr.getAttribute('data-customer') || '') : '';
                    if(!cust && tr){ var tds = tr.querySelectorAll('td'); if(tds.length>1) cust = tds[1].textContent.trim(); }
                    if(idx===0){ firstCust = cust; firstDate = date; } else {
                        if((cust||'') !== (firstCust||'') || (date||'') !== (firstDate||'')){ allSame = false; }
                    }
                });
                if(!allSame){ e.preventDefault(); alert(window.RNTranslate('common.client_print_requires_all_selected_services_to_belong_to_the_same_customer_and_the_same_date')); return; }
                handleFormSubmit(e);
            });
        } else {
            console.warn("Print form ('bulkPrintForm') not found.");
        }

        if (pdfForm) {
            console.log("Attaching submit listener to the PDF form.");
            pdfForm.addEventListener('submit', function(e){
                // re-check groupable condition before submitting
                var selected = document.querySelectorAll('.bulk-select:checked');
                if(selected.length === 0){ e.preventDefault(); alert(window.RNTranslate('common.client_please_select_at_least_one_item_to_create_pdf')); return; }
                var firstCust = null, firstDate = null, allSame = true;
                selected.forEach(function(cb, idx){
                    var tr = cb.closest('tr');
                    var date = tr ? (tr.dataset.date || tr.getAttribute('data-date') || '') : '';
                    var cust = tr ? (tr.dataset.customer || tr.getAttribute('data-customer') || '') : '';
                    if(!cust && tr){ var tds = tr.querySelectorAll('td'); if(tds.length>1) cust = tds[1].textContent.trim(); }
                    if(idx===0){ firstCust = cust; firstDate = date; } else {
                        if((cust||'') !== (firstCust||'') || (date||'') !== (firstDate||'')){ allSame = false; }
                    }
                });
                if(!allSame){ e.preventDefault(); alert(window.RNTranslate('common.client_pdf_requires_all_selected_services_to_belong_to_the_same_customer_and_the_same_date')); return; }
                handleFormSubmit(e);
            });
        } else {
            console.warn("PDF form ('bulkPdfForm') not found.");
        }

        // Initial UI check on page load to hide the bar
        updateBulkUI();
        console.log("Initialization complete.");
    }

    // --- Bulk Delete Modal Function ---
    window.showBulkDeleteModal = function(selectedIds, entity, form) {
        // Most lists have one kind of delete: ask once, then submit.
        if (form.getAttribute('data-delete-mode') !== 'choice') {
            Swal.fire({
                title: (form.getAttribute('data-delete-title') || '').replace(':count', selectedIds.length),
                text: form.getAttribute('data-delete-text') || '',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: form.getAttribute('data-delete-label') || window.RNTranslate('common.delete'),
                cancelButtonText: form.getAttribute('data-cancel-label') || window.RNTranslate('common.cancel')
            }).then(function(result) {
                if (result.isConfirmed) { submitBulkDelete(selectedIds, form, null); }
            });
            return;
        }
        // Pages may deny "Delete All Data" (admins only) and supply its exact warning text.
        var fullDeleteAllowed = form.getAttribute('data-full-delete') === 'allowed';
        var fullDeleteWarning = form.getAttribute('data-full-delete-warning') || '';
        var fullDeleteConfirm = form.getAttribute('data-full-delete-confirm');
        var escapeHtml = function (value) {
            var div = document.createElement('div');
            div.textContent = value;
            return div.innerHTML;
        };
        Swal.fire({
            title: 'Delete ' + selectedIds.length + ' ' + entity,
            html: '<p>Choose delete type:</p>' +
                  '<div style="text-align: left; margin: 20px 0;">' +
                  '<p style="margin: 10px 0;"><strong>Profile Only:</strong> Delete only the profile data</p>' +
                  (fullDeleteAllowed
                      ? '<p style="margin: 10px 0;" class="text-danger"><strong>' + escapeHtml(window.RNTranslate('common.client_delete_all_data')) + ':</strong> ' + escapeHtml(fullDeleteWarning) + '</p>'
                      : '') +
                  '</div>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Delete Profile Only',
            cancelButtonText: window.RNTranslate('common.cancel'),
            didOpen: function() {
                if (!fullDeleteAllowed) { return; }
                // Add additional button for full delete
                const popup = Swal.getPopup();
                const footerDiv = popup.querySelector('.swal2-actions');

                // Create Delete All Data button
                const deleteAllBtn = document.createElement('button');
                deleteAllBtn.className = 'btn btn-danger ml-2';
                deleteAllBtn.textContent = window.RNTranslate('common.client_delete_all_data');
                deleteAllBtn.style.marginLeft = '10px';
                deleteAllBtn.onclick = function() {
                    if (fullDeleteConfirm && !window.confirm(fullDeleteConfirm)) { return; }
                    submitBulkDelete(selectedIds, form, 'fullDelete');
                    Swal.close();
                };
                footerDiv.appendChild(deleteAllBtn);
            }
        }).then(function(result) {
            if (result.isConfirmed) {
                // Profile only delete was clicked
                submitBulkDelete(selectedIds, form, 'profileOnly');
            }
        });
    };

    // --- Submit Bulk Delete ---
    window.submitBulkDelete = function(selectedIds, form, deleteType) {
        console.log("Submitting bulk delete with type: " + deleteType);

        // Set delete type (only forms that offer a choice carry this field)
        var deleteTypeInput = document.getElementById('bulkDeleteType');
        if (deleteTypeInput && deleteType) { deleteTypeInput.value = deleteType; }

        // Clear old inputs
        form.querySelectorAll('input[name="selected[]"]').forEach(function(inp) {
            inp.remove();
        });

        // Add selected IDs
        selectedIds.forEach(function(id) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected[]';
            input.value = id;
            form.appendChild(input);
        });

        // Submit the form
        form.submit();
    };

    // --- Script Entry Point ---
    // We must wait for the DOM to be fully loaded before we can safely query for elements.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeBulkActions);
    } else {
        // The DOM was already ready, run immediately.
        initializeBulkActions();
    }

})();
</script>
<?php /**PATH C:\xampp\htdocs\pos\resources\views/livewire/partials/bulk-actions.blade.php ENDPATH**/ ?>
