@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Add Shipment') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Add Shipment {{ $gatePass ? '— against ' . $gatePass->gate_pass_no : '(Direct)' }}</h5>
            <a href="{{ route('inventory.shipments.index') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
            @if($gatePass)
                <div class="alert alert-info">This shipment references Gate Pass <strong>{{ $gatePass->gate_pass_no }}</strong> — stock has already left via the gate pass, so this shipment is a logistics/status record only.</div>
            @endif
            <form method="POST" action="{{ route('inventory.shipments.store') }}">
                @csrf
                @if($gatePass)
                    <input type="hidden" name="gate_pass_id" value="{{ $gatePass->id }}">
                @endif

                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Buyer</label>
                        <select name="buyer_id" class="form-control inv-select2">
                            <option value="">— Select —</option>
                            @foreach($buyers as $buyer)
                                <option value="{{ $buyer->id }}" @selected(old('buyer_id', $gatePass->buyer_id ?? '') == $buyer->id)>{{ $buyer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @unless($gatePass)
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Store <span class="text-danger">*</span></label>
                            <select name="store_id" class="form-control inv-select2" required>
                                <option value="">— Select —</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}" @selected(old('store_id') == $store->id)>{{ $store->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Gate Pass (optional)</label>
                            <select class="form-control inv-select2" onchange="if(this.value) window.location='{{ route('inventory.shipments.create') }}?gate_pass_id='+this.value">
                                <option value="">— Direct shipment —</option>
                                @foreach($gatePasses as $gp)
                                    <option value="{{ $gp->id }}">{{ $gp->gate_pass_no }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endunless
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Shipment Date <span class="text-danger">*</span></label>
                        <input type="date" name="shipment_date" class="form-control" value="{{ old('shipment_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Invoice No</label>
                        <input type="text" name="invoice_no" class="form-control" value="{{ old('invoice_no') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Packing List No</label>
                        <input type="text" name="packing_list_no" class="form-control" value="{{ old('packing_list_no') }}">
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
                    </div>
                </div>

                <hr>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Items</h6>
                    @unless($gatePass)
                        <button type="button" class="btn btn-sm btn-outline-primary" data-line-items-add="shp"><i class="fa-solid fa-plus"></i> Add Row</button>
                    @endunless
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle">
                        <thead><tr><th style="min-width:220px">Item</th><th style="width:160px">Quantity</th><th style="width:40px"></th></tr></thead>
                        <tbody id="shpRowsBody">
                            @php $lines = old('items', $gatePass ? $gatePass->items->map(fn ($i) => ['item_id' => $i->item_id, 'quantity' => $i->quantity])->all() : [[]]); @endphp
                            @foreach($lines as $index => $line)
                                <tr>
                                    <td>
                                        <select name="items[{{ $index }}][item_id]" class="form-control inv-select2" required @if($gatePass) disabled @endif>
                                            <option value="">— Select —</option>
                                            @foreach($items as $item)
                                                <option value="{{ $item->id }}" @selected(($line['item_id'] ?? null) == $item->id)>{{ $item->item_code }} — {{ $item->item_name }}</option>
                                            @endforeach
                                        </select>
                                        @if($gatePass)
                                            <input type="hidden" name="items[{{ $index }}][item_id]" value="{{ $line['item_id'] }}">
                                        @endif
                                    </td>
                                    <td><input type="number" step="0.0001" min="0.0001" name="items[{{ $index }}][quantity]" class="form-control" value="{{ $line['quantity'] ?? '' }}" @if($gatePass) readonly @endif required></td>
                                    <td>@unless($gatePass)<button type="button" class="btn btn-sm btn-outline-danger" data-line-items-remove><i class="fa-solid fa-xmark"></i></button>@endunless</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <template id="shpRowTemplate">
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

                <button type="submit" class="btn btn-primary mt-3">Save Shipment</button>
                <a href="{{ route('inventory.shipments.index') }}" class="btn btn-light mt-3">Cancel</a>
            </form>
        </div>
    </div>
</div>

@include('sfl-inventory::admin.partials.select2-init')
@include('sfl-inventory::admin.partials.line-items-script')
@endsection
