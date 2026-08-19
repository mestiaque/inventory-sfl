{{--
    Barcode "scan to receive" — pairs with an <input data-barcode-scan="{prefix}">
    above a line-items table built by line-items-script.blade.php (same
    {prefix}RowsBody / {prefix}RowTemplate / [data-line-items-add] contract).
    A physical barcode scanner just types the code + Enter, so this listens
    for Enter on the scan input, matches it against each item <option>'s
    data-barcode, and drops that item into the first empty row (adding a new
    row via the existing "Add Row" button if none is empty).
--}}
@push('js')
<script>
    (function () {
        function findItemIdByBarcode(prefix, code) {
            const rowsBody = document.getElementById(prefix + 'RowsBody');
            if (!rowsBody) {
                return null;
            }
            const option = rowsBody.querySelector('select[name$="[item_id]"] option[data-barcode="' + CSS.escape(code) + '"]');
            return option ? option.value : null;
        }

        function firstEmptyItemSelect(prefix) {
            const rowsBody = document.getElementById(prefix + 'RowsBody');
            if (!rowsBody) {
                return null;
            }
            const selects = rowsBody.querySelectorAll('select[name$="[item_id]"]');
            for (const select of selects) {
                if (!select.value) {
                    return select;
                }
            }
            return null;
        }

        function setSelectValue(select, itemId) {
            select.value = itemId;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            if (window.jQuery && jQuery(select).hasClass('select2-hidden-accessible')) {
                jQuery(select).trigger('change');
            }
        }

        document.querySelectorAll('input[data-barcode-scan]').forEach(function (input) {
            const prefix = input.getAttribute('data-barcode-scan');
            const feedback = document.getElementById(prefix + 'BarcodeScanFeedback');

            input.addEventListener('keydown', function (event) {
                if (event.key !== 'Enter') {
                    return;
                }
                event.preventDefault();

                const code = input.value.trim();
                input.value = '';
                if (!code) {
                    return;
                }

                const itemId = findItemIdByBarcode(prefix, code);
                if (!itemId) {
                    if (feedback) {
                        feedback.textContent = 'No item found for barcode: ' + code;
                        feedback.className = 'form-text text-danger';
                    }
                    return;
                }

                let target = firstEmptyItemSelect(prefix);
                if (!target) {
                    const addBtn = document.querySelector('[data-line-items-add="' + prefix + '"]');
                    addBtn?.click();
                    target = firstEmptyItemSelect(prefix);
                }

                if (target) {
                    setSelectValue(target, itemId);
                    target.closest('tr')?.querySelector('[data-role="qty"], input[name$="[received_qty]"]')?.focus();
                }

                if (feedback) {
                    feedback.textContent = 'Scanned: ' + code;
                    feedback.className = 'form-text text-success';
                }
            });
        });
    })();
</script>
@endpush
