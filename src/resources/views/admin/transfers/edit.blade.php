@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Edit Transfer ' . $transfer->transfer_no) }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Edit Transfer — {{ $transfer->transfer_no }}</h5>
            <a href="{{ route('inventory.transfers.show', $transfer) }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('inventory.transfers.update', $transfer) }}">
                @csrf @method('PUT')
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">From Store <span class="text-danger">*</span></label>
                        <select name="from_store_id" class="form-control inv-select2" required>
                            <option value="">— Select —</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" @selected(old('from_store_id', $transfer->from_store_id) == $store->id)>{{ $store->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">To Store <span class="text-danger">*</span></label>
                        <select name="to_store_id" class="form-control inv-select2" required>
                            <option value="">— Select —</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" @selected(old('to_store_id', $transfer->to_store_id) == $store->id)>{{ $store->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Transfer Date <span class="text-danger">*</span></label>
                        <input type="date" name="transfer_date" class="form-control" value="{{ old('transfer_date', optional($transfer->transfer_date)->format('Y-m-d')) }}" required>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2">{{ old('remarks', $transfer->remarks) }}</textarea>
                    </div>
                </div>

                <hr>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Items</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-line-items-add="trf"><i class="fa-solid fa-plus"></i> Add Row</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle">
                        <thead><tr><th style="min-width:220px">Item</th><th style="width:160px">Quantity</th><th style="width:40px"></th></tr></thead>
                        <tbody id="trfRowsBody">
                            @foreach(old('items', $transfer->items->map(fn ($i) => ['item_id' => $i->item_id, 'quantity' => (float) $i->quantity])->all()) as $index => $line)
                                <tr>
                                    <td>
                                        <select name="items[{{ $index }}][item_id]" class="form-control inv-select2" required>
                                            <option value="">— Select —</option>
                                            @foreach($items as $item)
                                                <option value="{{ $item->id }}" data-store="{{ $item->opening_store_id }}" @selected(($line['item_id'] ?? null) == $item->id)>{{ $item->item_code }} — {{ $item->item_name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" step="0.0001" min="0.0001" name="items[{{ $index }}][quantity]" class="form-control" value="{{ $line['quantity'] ?? '' }}" required></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger" data-line-items-remove><i class="fa-solid fa-xmark"></i></button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <template id="trfRowTemplate">
                    <tr>
                        <td>
                            <select name="items[__INDEX__][item_id]" class="form-control inv-select2" required>
                                <option value="">— Select —</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->id }}" data-store="{{ $item->opening_store_id }}">{{ $item->item_code }} — {{ $item->item_name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" step="0.0001" min="0.0001" name="items[__INDEX__][quantity]" class="form-control" required></td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger" data-line-items-remove><i class="fa-solid fa-xmark"></i></button></td>
                    </tr>
                </template>

                <button type="submit" class="btn btn-primary mt-3">Save Changes</button>
                <a href="{{ route('inventory.transfers.show', $transfer) }}" class="btn btn-light mt-3">Cancel</a>
            </form>
        </div>
    </div>
</div>

@include('sfl-inventory::admin.partials.select2-init')
@include('sfl-inventory::admin.partials.line-items-script')
@endsection
