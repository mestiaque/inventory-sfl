@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Merge Duplicate Items') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Merge Duplicate Items</h4>
            <a href="{{ route('inventory.items.index') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                Pick the item you want to <strong>keep</strong>, then pick the duplicate item(s) that should be merged into it.
                Every GRN, Purchase Order/Requisition, Store Requisition, Issue, Stock Transfer, Production Consumption,
                Finished Goods Receive, Gate Pass, Shipment, Stock Adjustment and stock ledger entry currently pointing at a
                duplicate is moved onto the kept item — nothing is lost, it's just re-labelled. The duplicate(s) are then
                deleted (moved to Trash).
            </div>

            <form method="POST" action="{{ route('inventory.items.merge') }}" id="itemMergeForm">
                @csrf

                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Keep this item <span class="text-danger">*</span></label>
                        <select name="keep_item_id" id="keepItemSelect" class="form-control form-control-sm inv-select2" required>
                            <option value="">— Select the item to keep —</option>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}" data-unit="{{ $item->unit_id }}" @selected(old('keep_item_id') == $item->id)>
                                    {{ $item->item_code }} — {{ $item->item_name }} ({{ $item->unit?->short_name ?? 'no unit' }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">This item's code, stores and history stay exactly as they are.</div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Duplicate item(s) to merge away <span class="text-danger">*</span></label>
                        <select name="duplicate_item_ids[]" id="duplicateItemsSelect" class="form-control form-control-sm inv-select2" multiple required>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}" data-unit="{{ $item->unit_id }}" @selected(collect(old('duplicate_item_ids', []))->contains($item->id))>
                                    {{ $item->item_code }} — {{ $item->item_name }} ({{ $item->unit?->short_name ?? 'no unit' }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Only items using the same unit as the kept item can be merged into it.</div>
                    </div>
                </div>

                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="confirmMerge" required>
                    <label class="form-check-label" for="confirmMerge">
                        I understand the duplicate item(s) will be deleted and this cannot be easily undone.
                    </label>
                </div>

                <button type="submit" class="btn btn-danger btn-sm">
                    <i class="fa-solid fa-code-merge"></i> Merge &amp; Delete Duplicate(s)
                </button>
                <a href="{{ route('inventory.items.index') }}" class="btn btn-light btn-sm">Cancel</a>
            </form>
        </div>
    </div>
</div>

@include('sfl-inventory::admin.partials.select2-init')
@push('js')
<script>
    document.getElementById('itemMergeForm')?.addEventListener('submit', function (e) {
        var keepOpt = document.getElementById('keepItemSelect');
        var keepVal = keepOpt.value;
        var dupVals = Array.from(document.getElementById('duplicateItemsSelect').selectedOptions).map(o => o.value);

        if (keepVal && dupVals.includes(keepVal)) {
            e.preventDefault();
            alert('The item you are keeping can\'t also be selected as a duplicate to merge away.');
        }
    });
</script>
@endpush
@endsection
