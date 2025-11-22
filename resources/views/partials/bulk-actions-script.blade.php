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
        var deleteForm = document.getElementById('bulkDeleteForm');
        var printForm = document.getElementById('bulkPrintForm');
        var allCheckboxes = document.querySelectorAll('.bulk-select');
        
        // Dynamically find the "Select All" checkbox on the page and assign it the correct class for our script to use.
        var masterSelectors = ['#selectAllProducts','#selectAllBrands','#selectAllCategories','#selectAllUnits','#selectAllPurchases','#selectAllSales','#selectAllDamage','#selectAllProvidedServices','#selectAllCustomers','#selectAllSuppliers'];
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
            console.error("Critical Error: Bulk actions UI wrapper ('bulkActionsWrapper') not found. Script cannot function.");
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
            if (printBtn) printBtn.disabled = !hasSelection;
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
                alert("Please select at least one item to " + (form.id.includes('Print') ? 'print.' : 'delete.'));
                return;
            }
            
            console.log("Found " + selectedIds.length + " selected IDs:", selectedIds);

            // Specific confirmation for delete action
            if (form.id === 'bulkDeleteForm') {
                if (!confirm('Are you sure you want to delete ' + selectedIds.length + ' ' + entity + '?')) {
                    console.log("User cancelled the delete operation.");
                    return;
                }
            }

            // Clear any old hidden inputs from previous attempts
            form.querySelectorAll('input[name="ids[]"]').forEach(function(inp) {
                inp.remove();
            });
            console.log("Cleared old hidden 'ids[]' inputs from the form.");

            // Add current IDs as new hidden inputs
            selectedIds.forEach(function(id) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                form.appendChild(input);
            });
            console.log("Appended " + selectedIds.length + " new hidden inputs to the form.");

            // Now, submit the form programmatically
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
            printForm.addEventListener('submit', handleFormSubmit);
        } else {
            console.warn("Print form ('bulkPrintForm') not found.");
        }

        // Initial UI check on page load to hide the bar
        updateBulkUI();
        console.log("Initialization complete.");
    }

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