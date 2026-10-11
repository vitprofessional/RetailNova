<style>
/* Reflow the existing controls; keep a single authoritative input per field. */
@media (max-width: 992px) {
    .rn-sale-mobile-table { min-width: 0 !important; width: 100%; }
    .rn-sale-mobile-table thead { display: none; }
    .rn-sale-mobile-table tbody { display: block; }
    .rn-sale-mobile-table tbody tr {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        border: 1px solid #dee2e6;
        border-radius: 8px;
        margin-bottom: 12px;
        padding: 8px;
    }
    .rn-sale-mobile-table tbody td {
        display: block;
        min-width: 0 !important;
        border: 0;
        text-align: left !important;
        overflow-wrap: anywhere;
    }
    .rn-sale-mobile-table tbody td[data-label]::before {
        content: attr(data-label);
        display: block;
        font-size: .8rem;
        font-weight: 600;
        margin-bottom: 4px;
    }
    .rn-sale-mobile-table tbody td:nth-child(2),
    .rn-sale-mobile-table tbody td:nth-child(3),
    .rn-sale-mobile-table tbody td[data-label="Serial Numbers"],
    .rn-sale-mobile-table tbody td[colspan] { grid-column: 1 / -1; }
    .rn-sale-mobile-table .form-control { width: 100%; min-width: 0; }
    .rn-sale-mobile-table .d-flex { flex-wrap: wrap; gap: 4px; }
    .rn-sale-mobile-table .serial-note,
    .rn-sale-mobile-table .stock-note { margin-top: 4px; }
    .rn-sale-mobile-table button { min-height: 36px; }
    #saleSerialModal .modal-dialog { margin: 8px; }
    #saleSerialModal .modal-body { max-height: 70vh; overflow-y: auto; }
}
</style>
