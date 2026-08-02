{{--
    Generic repeatable line-items table behavior, reused by every document
    form with an items[] array (PO, GRN, Requisition, Issue, Transfer,
    Consumption, FG Receive, Gate Pass, Shipment, Adjustment).

    Markup contract:
    - A <template id="{prefix}RowTemplate"> containing one <tr> of inputs
      named items[__INDEX__][field].
    - A <tbody id="{prefix}RowsBody"> to append rows into.
    - A button [data-line-items-add="{prefix}"] to add a row.
    - Row remove buttons: [data-line-items-remove] inside each row.
    - Optional per-row qty/rate inputs with [data-role="qty"] / [data-role="rate"]
      and an amount display [data-role="amount"] auto-computed as qty * rate.
--}}
@push('js')
<script>
    (function () {
        let lineItemRowIndex = 1000000; // always-increasing, never reused

        function computeRowAmount(row) {
            const qty = parseFloat(row.querySelector('[data-role="qty"]')?.value || 0);
            const rate = parseFloat(row.querySelector('[data-role="rate"]')?.value || 0);
            const amountField = row.querySelector('[data-role="amount"]');
            if (amountField) {
                amountField.value = (qty * rate).toFixed(2);
            }
        }

        function bindRow(row) {
            row.querySelectorAll('[data-role="qty"], [data-role="rate"]').forEach(function (input) {
                input.addEventListener('input', function () { computeRowAmount(row); });
            });
            const removeBtn = row.querySelector('[data-line-items-remove]');
            removeBtn?.addEventListener('click', function () {
                if (document.querySelectorAll('#' + row.closest('tbody').id + ' tr').length > 1) {
                    row.remove();
                }
            });
            invSelect2Init(row);
        }

        document.querySelectorAll('[data-line-items-add]').forEach(function (btn) {
            const prefix = btn.getAttribute('data-line-items-add');
            btn.addEventListener('click', function () {
                const template = document.getElementById(prefix + 'RowTemplate');
                const body = document.getElementById(prefix + 'RowsBody');
                const html = template.innerHTML.replaceAll('__INDEX__', lineItemRowIndex++);
                const tempTable = document.createElement('table');
                tempTable.innerHTML = '<tbody>' + html + '</tbody>';
                const row = tempTable.querySelector('tr');
                body.appendChild(row);
                bindRow(row);
            });
        });

        document.querySelectorAll('tbody[id$="RowsBody"] tr').forEach(bindRow);
    })();
</script>
@endpush
