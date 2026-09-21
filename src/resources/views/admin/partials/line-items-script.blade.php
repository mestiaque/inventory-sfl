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

        function syncRowUnit(row) {
            const itemSelect = row.querySelector('select[name$="[item_id]"]');
            const unitField = row.querySelector('[data-role="unit"]');
            if (!itemSelect || !unitField) {
                return;
            }
            const option = itemSelect.options[itemSelect.selectedIndex];
            unitField.value = option?.getAttribute('data-unit') || '';
        }

        // An item option carrying data-color-id/data-size-id already has a
        // single fixed variant of its own (set on the Item Master) — that
        // row's color/size picker is hidden and disabled (so nothing is
        // submitted for it; the server falls back to the item's own fixed
        // value). An item with no fixed color/size is "generic" — multiple
        // variants of it can exist in stock, so the picker is shown and
        // required.
        function toggleRowVariantPickers(row) {
            const itemSelect = row.querySelector('select[name$="[item_id]"]');
            const colorSelect = row.querySelector('select[name$="[color_id]"]');
            const sizeSelect = row.querySelector('select[name$="[size_id]"]');
            if (!itemSelect || (!colorSelect && !sizeSelect)) {
                return;
            }
            const option = itemSelect.options[itemSelect.selectedIndex];
            const hasItem = !!itemSelect.value;
            const fixedColor = option?.getAttribute('data-color-id') || '';
            const fixedSize = option?.getAttribute('data-size-id') || '';

            [[colorSelect, fixedColor], [sizeSelect, fixedSize]].forEach(function (pair) {
                const select = pair[0];
                const fixedValue = pair[1];
                if (!select) {
                    return;
                }
                const isFixed = hasItem && !!fixedValue;
                select.style.display = isFixed ? 'none' : '';
                select.disabled = isFixed;
                select.required = hasItem && !isFixed;
                if (isFixed) {
                    select.value = '';
                }
                if ($(select).hasClass('select2-hidden-accessible')) {
                    $(select).trigger('change.select2');
                }
            });
        }

        // The document-level store field may be a live select, or locked to
        // a single store (disabled select + hidden input carrying the real
        // value, e.g. the one Finished Goods store) — read whichever
        // actually has the value.
        function currentDocStoreId() {
            const hidden = document.querySelector('input[type="hidden"][name="store_id"], input[type="hidden"][name="from_store_id"]');
            if (hidden) {
                return hidden.value;
            }
            const select = document.querySelector('select[name="store_id"], select[name="from_store_id"]');
            return select ? select.value : null;
        }

        // Items are fixed to exactly one store — once a document-level
        // store field exists on the page, item options carrying
        // data-store="{id}" are filtered down to that store so only items
        // that actually live there can be picked.
        function filterRowItemsByStore(row) {
            const storeId = currentDocStoreId();
            if (storeId === null) {
                return;
            }
            const itemSelect = row.querySelector('select[name$="[item_id]"]');
            if (!itemSelect) {
                return;
            }
            let selectedStillValid = false;
            Array.from(itemSelect.options).forEach(function (opt) {
                if (!opt.value) {
                    return;
                }
                const optStore = opt.getAttribute('data-store');
                const visible = !storeId || !optStore || optStore === storeId;
                opt.hidden = !visible;
                opt.disabled = !visible;
                if (opt.value === itemSelect.value && visible) {
                    selectedStillValid = true;
                }
            });
            if (!selectedStillValid && itemSelect.value) {
                itemSelect.value = '';
            }
            if ($(itemSelect).hasClass('select2-hidden-accessible')) {
                $(itemSelect).select2('destroy');
            }
            invSelect2Init(row);
        }

        function filterAllRowsByStore() {
            document.querySelectorAll('tbody[id$="RowsBody"] tr').forEach(filterRowItemsByStore);
        }

        function bindRow(row) {
            row.querySelectorAll('[data-role="qty"], [data-role="rate"]').forEach(function (input) {
                input.addEventListener('input', function () { computeRowAmount(row); });
            });
            const itemSelect = row.querySelector('select[name$="[item_id]"]');
            if (itemSelect) {
                itemSelect.addEventListener('change', function () { syncRowUnit(row); toggleRowVariantPickers(row); });
                $(itemSelect).on('change', function () { syncRowUnit(row); toggleRowVariantPickers(row); });
            }
            const removeBtn = row.querySelector('[data-line-items-remove]');
            removeBtn?.addEventListener('click', function () {
                if (document.querySelectorAll('#' + row.closest('tbody').id + ' tr').length > 1) {
                    row.remove();
                }
            });
            invSelect2Init(row);
            syncRowUnit(row);
            filterRowItemsByStore(row);
            toggleRowVariantPickers(row);
        }

        const storeSelect = document.querySelector('select[name="store_id"], select[name="from_store_id"]');
        if (storeSelect) {
            storeSelect.addEventListener('change', filterAllRowsByStore);
            $(storeSelect).on('change', filterAllRowsByStore);
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
