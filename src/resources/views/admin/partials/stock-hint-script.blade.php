{{--
    Live stock column for line-item forms (Requisition, Requisition Approval,
    Issue). Fetches Current / Reserved / Available from
    inventory.stock-balance for each row and flags an over-limit qty.

    Markup contract:
    - A cell [data-role="stock"] in each row. Item/color/size come from
      data-item-id / data-color-id / data-size-id on that cell when present,
      otherwise from the row's items[..][item_id|color_id|size_id] selects.
    - Store: data-store-id on the cell, else the page's store_id field.
    - Optional data-exclude-requisition="{id}" leaves that requisition's own
      reservation out of Available; data-requisition="{id}" adds the Buyer
      Store style balance for that requisition's buyer + style, which then
      also caps the qty.
    - Optional data-live-style on the cell (unsaved requisition form): the
      page's Buyer / Style fields are sent instead, so the Buyer Store style
      balance follows whatever is picked right now.
    - The row's qty input carries data-stock-qty="available" or "current" —
      which figure it must not exceed.
--}}
@push('js')
<script>
    (function () {
        const url = @json(route('inventory.stock-balance'));
        const fmt = function (n) { return (Math.round(n * 100) / 100).toString(); };

        function docStoreId() {
            const hidden = document.querySelector('input[type="hidden"][name="store_id"]');
            if (hidden) { return hidden.value; }
            const select = document.querySelector('select[name="store_id"]');
            return select ? select.value : '';
        }

        function rowValue(row, cell, field) {
            const fixed = cell.dataset[field + 'Id'];
            if (fixed !== undefined) { return fixed; }
            const select = row.querySelector('select[name$="[' + field + '_id]"]');
            return select && !(select.disabled && field !== 'item') ? select.value : '';
        }

        function checkQty(row) {
            const input = row.querySelector('[data-stock-qty]');
            const cell = row.querySelector('[data-role="stock"]');
            if (!input || !cell || cell.dataset.current === undefined) { return; }
            let limit = parseFloat(cell.dataset[input.dataset.stockQty] || 0);
            if (cell.dataset.styleBalance !== undefined) { limit = Math.min(limit, parseFloat(cell.dataset.styleBalance)); }
            const over = parseFloat(input.value || 0) > limit + 0.0001;
            input.classList.toggle('is-invalid', over);
            input.title = over ? 'More than the stock that can be given (' + fmt(limit) + ')' : '';
        }

        function refresh(row) {
            const cell = row.querySelector('[data-role="stock"]');
            if (!cell) { return; }
            const storeId = cell.dataset.storeId || docStoreId();
            const itemId = rowValue(row, cell, 'item');
            delete cell.dataset.current;
            delete cell.dataset.available;
            delete cell.dataset.styleBalance;
            if (!storeId || !itemId) { cell.innerHTML = '<span class="text-muted">—</span>'; checkQty(row); return; }

            const params = new URLSearchParams({ store_id: storeId, item_id: itemId });
            const colorId = rowValue(row, cell, 'color');
            const sizeId = rowValue(row, cell, 'size');
            if (colorId) { params.set('color_id', colorId); }
            if (sizeId) { params.set('size_id', sizeId); }
            if (cell.dataset.excludeRequisition) { params.set('exclude_requisition_id', cell.dataset.excludeRequisition); }
            if (cell.dataset.requisition) { params.set('requisition_id', cell.dataset.requisition); }
            if (cell.dataset.liveStyle !== undefined) {
                ['buyer_id', 'style', 'msfl_buyer_id', 'msfl_style_id'].forEach(function (name) {
                    const field = document.querySelector('[name="' + name + '"]');
                    if (field && field.value.trim()) { params.set(name, field.value.trim()); }
                });
            }

            cell.innerHTML = '<span class="text-muted small">…</span>';
            fetch(url + '?' + params, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(function (r) { return r.ok ? r.json() : Promise.reject(r); })
                .then(function (s) {
                    cell.dataset.current = s.current;
                    cell.dataset.available = s.available;
                    if (s.scoped && s.style) {
                        // Buyer Store requisition: only this buyer's / style's stock.
                        cell.dataset.styleBalance = s.style.balance;
                        cell.innerHTML =
                            '<div class="small text-muted">' + s.style.label + '</div>' +
                            '<div class="small">Received: ' + fmt(s.style.received) + ' · Issued: ' + fmt(s.style.issued) + '</div>' +
                            '<div class="small">Stock: <strong class="' + (s.style.balance <= 0 ? 'text-danger' : 'text-success') + '">' + fmt(s.style.balance) + '</strong></div>';
                        checkQty(row);
                        return;
                    }
                    cell.innerHTML =
                        '<div class="small">Current: <strong class="' + (s.current <= 0 ? 'text-danger' : '') + '">' + fmt(s.current) + '</strong></div>' +
                        '<div class="small text-muted">Reserved: ' + fmt(s.reserved) + '</div>' +
                        '<div class="small">Available: <strong class="' + (s.available <= 0 ? 'text-danger' : 'text-success') + '">' + fmt(s.available) + '</strong></div>';
                    if (s.style) {
                        cell.dataset.styleBalance = s.style.balance;
                        cell.innerHTML += '<div class="small border-top mt-1 pt-1" title="Received ' + fmt(s.style.received) + ', issued ' + fmt(s.style.issued) + '">' + s.style.label +
                            ': <strong class="' + (s.style.balance <= 0 ? 'text-danger' : 'text-success') + '">' + fmt(s.style.balance) + '</strong></div>';
                    }
                    checkQty(row);
                })
                .catch(function () { cell.innerHTML = '<span class="text-muted small">n/a</span>'; });
        }

        function allRows() {
            return Array.from(document.querySelectorAll('[data-role="stock"]')).map(function (c) { return c.closest('tr'); });
        }

        $(document).on('change', 'tr select', function () {
            const row = this.closest('tr');
            if (row && row.querySelector('[data-role="stock"]') && !this.matches('[name="store_id"]')) { refresh(row); }
        });
        $(document).on('input', '[data-stock-qty]', function () { checkQty(this.closest('tr')); });
        $(document).on('change', 'select[name="store_id"]', function () { allRows().forEach(refresh); });
        // Buyer / Style changed on a live form — re-read each row's style balance
        // (after the page's own handlers have rebuilt dependent dropdowns).
        $(document).on('change', '[name="buyer_id"], [name="style"], [name="msfl_buyer_id"], [name="msfl_style_id"]', function () {
            setTimeout(function () {
                allRows().forEach(function (r) { if (r.querySelector('[data-role="stock"]').dataset.liveStyle !== undefined) { refresh(r); } });
            }, 0);
        });
        // Rows added later by the line-items script start empty — show the placeholder.
        $(document).on('click', '[data-line-items-add]', function () { setTimeout(function () { allRows().forEach(function (r) { if (!r.querySelector('[data-role="stock"]').innerHTML.trim()) { refresh(r); } }); }, 0); });

        allRows().forEach(refresh);
    })();
</script>
@endpush
