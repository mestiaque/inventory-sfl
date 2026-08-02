@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Add FG Receive') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Add Finished Goods Receive</h5>
            <a href="{{ route('inventory.fg-receives.index') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('inventory.fg-receives.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Style</label>
                        <input type="text" name="style" class="form-control" value="{{ old('style') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Buyer</label>
                        <select name="buyer_id" class="form-control inv-select2">
                            <option value="">— Select —</option>
                            @foreach($buyers as $buyer)
                                <option value="{{ $buyer->id }}" @selected(old('buyer_id') == $buyer->id)>{{ $buyer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Order Ref</label>
                        <input type="text" name="order_ref" class="form-control" value="{{ old('order_ref') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Finished Goods Store <span class="text-danger">*</span></label>
                        <select name="store_id" class="form-control inv-select2" required>
                            <option value="">— Select —</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" @selected(old('store_id') == $store->id)>{{ $store->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Receive Date <span class="text-danger">*</span></label>
                        <input type="date" name="receive_date" class="form-control" value="{{ old('receive_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
                    </div>
                </div>

                <hr>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Items</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-line-items-add="fgr"><i class="fa-solid fa-plus"></i> Add Row</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle">
                        <thead><tr><th style="min-width:220px">Finished Good Item</th><th style="width:160px">Quantity</th><th style="width:40px"></th></tr></thead>
                        <tbody id="fgrRowsBody">
                            <tr>
                                <td>
                                    <select name="items[0][item_id]" class="form-control inv-select2" required>
                                        <option value="">— Select —</option>
                                        @foreach($items as $item)
                                            <option value="{{ $item->id }}">{{ $item->item_code }} — {{ $item->item_name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" step="0.0001" min="0.0001" name="items[0][quantity]" class="form-control" required></td>
                                <td><button type="button" class="btn btn-sm btn-outline-danger" data-line-items-remove><i class="fa-solid fa-xmark"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <template id="fgrRowTemplate">
                    <tr>
                        <td>
                            <select name="items[__INDEX__][item_id]" class="form-control inv-select2" required>
                                <option value="">— Select —</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->id }}">{{ $item->item_code }} — {{ $item->item_name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" step="0.0001" min="0.0001" name="items[__INDEX__][quantity]" class="form-control" required></td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger" data-line-items-remove><i class="fa-solid fa-xmark"></i></button></td>
                    </tr>
                </template>

                <button type="submit" class="btn btn-primary mt-3">Post Receive &amp; Update Stock</button>
                <a href="{{ route('inventory.fg-receives.index') }}" class="btn btn-light mt-3">Cancel</a>
            </form>
        </div>
    </div>
</div>

@include('sfl-inventory::admin.partials.select2-init')
@include('sfl-inventory::admin.partials.line-items-script')
@endsection
