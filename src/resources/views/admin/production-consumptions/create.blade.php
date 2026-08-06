@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Add Production Consumption') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Add Production Consumption</h5>
            <a href="{{ route('inventory.production-consumptions.index') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('inventory.production-consumptions.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Department <span class="text-danger">*</span></label>
                        <select name="department_id" class="form-control inv-select2" required>
                            <option value="">— Select —</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Floor Store <span class="text-danger">*</span></label>
                        <select name="store_id" class="form-control inv-select2" required>
                            <option value="">— Select —</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" @selected(old('store_id') == $store->id)>{{ $store->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Style</label>
                        <input type="text" name="style" class="form-control" value="{{ old('style') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Order Ref</label>
                        <input type="text" name="order_ref" class="form-control" value="{{ old('order_ref') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Consumption Date <span class="text-danger">*</span></label>
                        <input type="date" name="consumption_date" class="form-control" value="{{ old('consumption_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
                    </div>
                </div>

                <hr>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Items</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-line-items-add="pc"><i class="fa-solid fa-plus"></i> Add Row</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle">
                        <thead><tr><th style="min-width:220px">Item</th><th style="width:160px">Consumed Qty</th><th style="width:160px">Waste Qty</th><th style="width:40px"></th></tr></thead>
                        <tbody id="pcRowsBody">
                            <tr>
                                <td>
                                    <select name="items[0][item_id]" class="form-control inv-select2" required>
                                        <option value="">— Select —</option>
                                        @foreach($items as $item)
                                            <option value="{{ $item->id }}" data-store="{{ $item->opening_store_id }}">{{ $item->item_code }} — {{ $item->item_name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" step="0.0001" min="0" name="items[0][consumed_qty]" class="form-control" required></td>
                                <td><input type="number" step="0.0001" min="0" name="items[0][waste_qty]" class="form-control" value="0"></td>
                                <td><button type="button" class="btn btn-sm btn-outline-danger" data-line-items-remove><i class="fa-solid fa-xmark"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <template id="pcRowTemplate">
                    <tr>
                        <td>
                            <select name="items[__INDEX__][item_id]" class="form-control inv-select2" required>
                                <option value="">— Select —</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->id }}" data-store="{{ $item->opening_store_id }}">{{ $item->item_code }} — {{ $item->item_name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" step="0.0001" min="0" name="items[__INDEX__][consumed_qty]" class="form-control" required></td>
                        <td><input type="number" step="0.0001" min="0" name="items[__INDEX__][waste_qty]" class="form-control" value="0"></td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger" data-line-items-remove><i class="fa-solid fa-xmark"></i></button></td>
                    </tr>
                </template>

                <button type="submit" class="btn btn-primary mt-3">Post Consumption &amp; Update Stock</button>
                <a href="{{ route('inventory.production-consumptions.index') }}" class="btn btn-light mt-3">Cancel</a>
            </form>
        </div>
    </div>
</div>

@include('sfl-inventory::admin.partials.select2-init')
@include('sfl-inventory::admin.partials.line-items-script')
@endsection
