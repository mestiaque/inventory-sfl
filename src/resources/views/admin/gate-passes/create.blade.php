@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Add Gate Pass') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Add Gate Pass {{ $shipment ? '— against ' . $shipment->shipment_no : '(Direct)' }}</h5>
            <a href="{{ route('inventory.gate-passes.index') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
            @if($shipment)
                <div class="alert alert-info">Approving this gate pass is the actual stock-out event — the goods leave the factory here.</div>
            @endif
            <form method="POST" action="{{ route('inventory.gate-passes.store') }}">
                @csrf
                @if($shipment)
                    <input type="hidden" name="shipment_id" value="{{ $shipment->id }}">
                @endif

                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Buyer</label>
                        <select name="buyer_id" class="form-control inv-select2" @if($shipment) disabled @endif>
                            <option value="">— Select —</option>
                            @foreach($buyers as $buyer)
                                <option value="{{ $buyer->id }}" @selected(old('buyer_id', $shipment->buyer_id ?? '') == $buyer->id)>{{ $buyer->name }}</option>
                            @endforeach
                        </select>
                        @if($shipment)
                            <input type="hidden" name="buyer_id" value="{{ $shipment->buyer_id }}">
                        @endif
                    </div>
                    @php $lockedStoreId = $shipment->store_id ?? $fgStore?->id; @endphp
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Store <span class="text-danger">*</span></label>
                        <select name="{{ $lockedStoreId ? '' : 'store_id' }}" class="form-control inv-select2" required @disabled($lockedStoreId)>
                            <option value="">— Select —</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" @selected(old('store_id', $lockedStoreId) == $store->id)>{{ $store->name }}</option>
                            @endforeach
                        </select>
                        @if($lockedStoreId)
                            <input type="hidden" name="store_id" value="{{ $lockedStoreId }}">
                        @endif
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Gate Pass Date <span class="text-danger">*</span></label>
                        <input type="date" name="gate_pass_date" class="form-control" value="{{ old('gate_pass_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Vehicle No</label>
                        <input type="text" name="vehicle_no" class="form-control" value="{{ old('vehicle_no') }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Driver Name</label>
                        <input type="text" name="driver_name" class="form-control" value="{{ old('driver_name') }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Driver Contact</label>
                        <input type="text" name="driver_contact" class="form-control" value="{{ old('driver_contact') }}">
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
                    </div>
                </div>

                <hr>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Items</h6>
                    @unless($shipment)
                        <button type="button" class="btn btn-sm btn-outline-primary" data-line-items-add="gp"><i class="fa-solid fa-plus"></i> Add Row</button>
                    @endunless
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle">
                        <thead><tr><th style="min-width:220px">Item</th><th style="width:160px">Quantity</th><th style="width:40px"></th></tr></thead>
                        <tbody id="gpRowsBody">
                            @php $lines = old('items', $shipment ? $shipment->items->map(fn ($i) => ['item_id' => $i->item_id, 'quantity' => $i->quantity])->all() : [[]]); @endphp
                            @foreach($lines as $index => $line)
                                <tr>
                                    <td>
                                        <select name="items[{{ $index }}][item_id]" class="form-control inv-select2" required @if($shipment) disabled @endif>
                                            <option value="">— Select —</option>
                                            @foreach($items as $item)
                                                <option value="{{ $item->id }}" @selected(($line['item_id'] ?? null) == $item->id)>{{ $item->item_code }} — {{ $item->item_name }}</option>
                                            @endforeach
                                        </select>
                                        @if($shipment)
                                            <input type="hidden" name="items[{{ $index }}][item_id]" value="{{ $line['item_id'] }}">
                                        @endif
                                    </td>
                                    <td><input type="number" step="0.0001" min="0.0001" name="items[{{ $index }}][quantity]" class="form-control" value="{{ $line['quantity'] ?? '' }}" @if($shipment) readonly @endif required></td>
                                    <td>@unless($shipment)<button type="button" class="btn btn-sm btn-outline-danger" data-line-items-remove><i class="fa-solid fa-xmark"></i></button>@endunless</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @unless($shipment)
                    <template id="gpRowTemplate">
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
                @endunless

                <button type="submit" class="btn btn-primary mt-3">Save Gate Pass</button>
                <a href="{{ route('inventory.gate-passes.index') }}" class="btn btn-light mt-3">Cancel</a>
            </form>
        </div>
    </div>
</div>

@include('sfl-inventory::admin.partials.select2-init')
@include('sfl-inventory::admin.partials.line-items-script')
@endsection
