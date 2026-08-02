@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('New Stock Adjustment') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">New Stock Adjustment</h5>
            <a href="{{ route('inventory.adjustments.index') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
            <div class="alert alert-info">The system quantity for each item is captured automatically at the moment you submit this form. Enter the physically counted quantity — the difference will be posted to the stock ledger once approved.</div>

            <form method="POST" action="{{ route('inventory.adjustments.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Store <span class="text-danger">*</span></label>
                        <select name="store_id" class="form-control inv-select2" required>
                            <option value="">— Select —</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" @selected(old('store_id') == $store->id)>{{ $store->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-control inv-select2" required>
                            @foreach(['physical_count' => 'Physical Count', 'damage' => 'Damage', 'lost' => 'Lost', 'excess' => 'Excess'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('type', 'physical_count') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Adjustment Date <span class="text-danger">*</span></label>
                        <input type="date" name="adjustment_date" class="form-control" value="{{ old('adjustment_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
                    </div>
                </div>

                <hr>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Items</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-line-items-add="adj"><i class="fa-solid fa-plus"></i> Add Row</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle">
                        <thead><tr><th style="min-width:220px">Item</th><th style="width:160px">Physical Qty</th><th style="width:40px"></th></tr></thead>
                        <tbody id="adjRowsBody">
                            <tr>
                                <td>
                                    <select name="items[0][item_id]" class="form-control inv-select2" required>
                                        <option value="">— Select —</option>
                                        @foreach($items as $item)
                                            <option value="{{ $item->id }}">{{ $item->item_code }} — {{ $item->item_name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" step="0.0001" min="0" name="items[0][physical_qty]" class="form-control" required></td>
                                <td><button type="button" class="btn btn-sm btn-outline-danger" data-line-items-remove><i class="fa-solid fa-xmark"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <template id="adjRowTemplate">
                    <tr>
                        <td>
                            <select name="items[__INDEX__][item_id]" class="form-control inv-select2" required>
                                <option value="">— Select —</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->id }}">{{ $item->item_code }} — {{ $item->item_name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" step="0.0001" min="0" name="items[__INDEX__][physical_qty]" class="form-control" required></td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger" data-line-items-remove><i class="fa-solid fa-xmark"></i></button></td>
                    </tr>
                </template>

                <button type="submit" class="btn btn-primary mt-3">Submit for Approval</button>
                <a href="{{ route('inventory.adjustments.index') }}" class="btn btn-light mt-3">Cancel</a>
            </form>
        </div>
    </div>
</div>

@include('sfl-inventory::admin.partials.select2-init')
@include('sfl-inventory::admin.partials.line-items-script')
@endsection
