import $ from 'jquery';
import 'datatables.net-dt/css/jquery.dataTables.css';
import 'datatables.net-buttons-dt/css/buttons.dataTables.css';

import dt from 'datatables.net-dt';
import buttons from 'datatables.net-buttons-dt';
import jszip from 'jszip';
import pdfMake from 'pdfmake/build/pdfmake';
import pdfFonts from 'pdfmake/build/vfs_fonts';
import 'datatables.net-buttons/js/buttons.html5.js';
import 'datatables.net-buttons/js/buttons.print.js';

pdfMake.vfs = pdfFonts.pdfMake.vfs;

window.$ = window.jQuery = $;
dt(window, $);
buttons(window, $);

$(function () {
    if ($('#purchaseTable').length) {
        $('#purchaseTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { extend: 'copy', className: 'btn btn-outline-secondary btn-sm' },
                { extend: 'csv', className: 'btn btn-outline-secondary btn-sm' },
                { extend: 'excel', className: 'btn btn-outline-secondary btn-sm' },
                { extend: 'pdf', className: 'btn btn-outline-secondary btn-sm' },
                { extend: 'print', className: 'btn btn-outline-secondary btn-sm' }
            ],
            order: [[3, 'desc']],
            responsive: true,
            columnDefs: [
                { targets: [3,4,5,6], className: 'dt-body-right' },
                { orderable: false, targets: [0,8] }
            ],
            pageLength: 25
        });

        $('#selectAll').on('change', function () {
            var checked = $(this).is(':checked');
            $('input.row-select').prop('checked', checked);
        });
    }
});

export default {};
