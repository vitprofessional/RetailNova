{{-- Asks for a cancellation reason before a cancel-sale form (or the bulk cancel form) is submitted. --}}
@php
    $saleCancelText = [
        'single' => __('sales.cancel_sale_prompt'),
        'bulk' => __('sales.cancel_selected_sales_prompt'),
        'placeholder' => __('sales.cancel_reason_placeholder'),
        'invalid' => __('sales.cancel_reason_invalid'),
        'confirm' => __('sales.confirm_cancel_sale'),
        'keep' => __('sales.keep_sale'),
    ];
@endphp
<script>
(function () {
    var text = @json($saleCancelText);

    function validReason(value) {
        var reason = String(value || '').trim();
        return reason.length >= 3 && reason.length <= 500 ? reason : null;
    }

    // Resolves with the trimmed reason, or null when the user keeps the sale.
    window.rnAskSaleCancelReason = function (bulk) {
        var title = bulk ? text.bulk : text.single;
        if (window.Swal && typeof window.Swal.fire === 'function') {
            return window.Swal.fire({
                title: title,
                input: 'textarea',
                inputPlaceholder: text.placeholder,
                inputAttributes: { maxlength: 500, 'aria-label': text.placeholder },
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: text.confirm,
                cancelButtonText: text.keep,
                confirmButtonColor: '#dc3545',
                inputValidator: function (value) { return validReason(value) ? undefined : text.invalid; }
            }).then(function (result) { return result.isConfirmed ? validReason(result.value) : null; });
        }
        var answer = window.prompt(title + '\n' + text.placeholder, '');
        if (answer === null) { return Promise.resolve(null); }
        if (!validReason(answer)) { window.alert(text.invalid); return Promise.resolve(null); }
        return Promise.resolve(validReason(answer));
    };

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form || !form.matches || !form.matches('form[data-cancel-sale-form]')) { return; }
        event.preventDefault();
        window.rnAskSaleCancelReason(false).then(function (reason) {
            if (!reason) { return; }
            form.querySelector('input[name="reason"]').value = reason;
            form.submit();
        });
    }, true);
})();
</script>
