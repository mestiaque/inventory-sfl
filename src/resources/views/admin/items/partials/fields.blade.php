{{-- props: item (optional, for edit), categories, units, brands, colors, sizes, stores --}}
<div class="row">
    <div class="col-md-3 mb-3">
        <label class="form-label">Item Code <span class="text-danger">*</span></label>
        <input type="text" name="item_code" class="form-control form-control-sm" value="{{ old('item_code', $item->item_code ?? '') }}" placeholder="e.g. SFL-SW-090" required>
        <div class="form-text">Enter the item's code yourself — it is not auto-generated.</div>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Item Name <span class="text-danger">*</span></label>
        <input type="text" name="item_name" class="form-control form-control-sm" value="{{ old('item_name', $item->item_name ?? '') }}" required>
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label">Category <span class="text-danger">*</span></label>
        <select name="category_id" class="form-control form-control-sm inv-select2" required>
            <option value="">— Select —</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id', $item->category_id ?? '') == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Department / Section</label>
        <select name="department_id" class="form-control form-control-sm inv-select2">
            <option value="">— None —</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}" @selected(old('department_id', $item->department_id ?? '') == $department->id)>{{ $department->name }}</option>
            @endforeach
        </select>
        <div class="form-text">Floor section that uses this item.</div>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Supplier</label>
        <select name="supplier_id" class="form-control form-control-sm inv-select2">
            <option value="">— None —</option>
            @foreach($suppliers as $supplier)
                <option value="{{ $supplier->id }}" @selected(old('supplier_id', $item->supplier_id ?? '') == $supplier->id)>{{ $supplier->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 mb-3">
        @php
            $itemBuyerIds = isset($item) && $item->exists
                ? $item->buyers()->pluck('inv_buyers.id')->push($item->buyer_id)->filter()->map(fn ($id) => (int) $id)->unique()->values()
                : collect();
        @endphp
        <input type="hidden" name="buyer_ids_present" value="1">
        <label class="form-label">Buyers</label>
        @if($merLinked ?? false)
            @php
                $mbLink = app(\ME\SflInventory\Services\MerchandisingLink::class);
                $mbSelected = collect(old('msfl_buyer_ids', $itemBuyerIds->map(fn ($id) => (string) ($mbLink->merBuyerIdFor($id) ?? \ME\SflInventory\Services\MerchandisingLink::LEGACY_BUYER . $id))->all()))->map(fn ($v) => (string) $v)->all();
                $mbKeep = $itemBuyerIds->filter(fn ($id) => ! $mbLink->merBuyerIdFor($id))
                    ->map(fn ($id) => \ME\SflInventory\Models\InvBuyer::withTrashed()->find($id))->filter();
            @endphp
            <select name="msfl_buyer_ids[]" class="form-control form-control-sm inv-select2" multiple data-placeholder="— None —">
                @foreach($merBuyersOptions as $b)
                    <option value="{{ $b->id }}" @selected(in_array((string) $b->id, $mbSelected, true))>{{ $b->name }}</option>
                @endforeach
                @foreach($mbKeep as $kb)
                    @php $kv = \ME\SflInventory\Services\MerchandisingLink::LEGACY_BUYER . $kb->id; @endphp
                    <option value="{{ $kv }}" @selected(in_array($kv, $mbSelected, true))>{{ $kb->name }} (Inventory)</option>
                @endforeach
            </select>
        @else
            @php $selBuyers = collect(old('buyer_ids', $itemBuyerIds->all()))->map(fn ($v) => (int) $v)->all(); @endphp
            <select name="buyer_ids[]" class="form-control form-control-sm inv-select2" multiple data-placeholder="— None —">
                @foreach($buyers as $buyer)
                    <option value="{{ $buyer->id }}" @selected(in_array((int) $buyer->id, $selBuyers, true))>{{ $buyer->name }}</option>
                @endforeach
            </select>
        @endif
        <div class="form-text">Only for buyer-specific items — pick as many buyers as needed.</div>
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label">Unit <span class="text-danger">*</span></label>
        <select name="unit_id" class="form-control form-control-sm inv-select2" required>
            <option value="">— Select —</option>
            @foreach($units as $unit)
                <option value="{{ $unit->id }}" @selected(old('unit_id', $item->unit_id ?? '') == $unit->id)>{{ $unit->name }} ({{ $unit->short_name }})</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Brand</label>
        <select name="brand_id" class="form-control form-control-sm inv-select2">
            <option value="">— None —</option>
            @foreach($brands as $brand)
                <option value="{{ $brand->id }}" @selected(old('brand_id', $item->brand_id ?? '') == $brand->id)>{{ $brand->name }}</option>
            @endforeach
        </select>
    </div>
    {{--
        Color/Size are no longer set on the Item Master — an item is generic
        by default now, and its actual color/size is picked per transaction
        line at Goods Receive (and inherited everywhere downstream from
        there). Commented out, not removed, so this is easy to bring back;
        the item_id, color_id and size_id columns/fields underneath are
        untouched.
    <div class="col-md-3 mb-3">
        <label class="form-label">Color</label>
        <select name="color_id" class="form-control form-control-sm inv-select2">
            <option value="">— None —</option>
            @foreach($colors as $color)
                <option value="{{ $color->id }}" @selected(old('color_id', $item->color_id ?? '') == $color->id)>{{ $color->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Size</label>
        <select name="size_id" class="form-control form-control-sm inv-select2">
            <option value="">— None —</option>
            @foreach($sizes as $size)
                <option value="{{ $size->id }}" @selected(old('size_id', $item->size_id ?? '') == $size->id)>{{ $size->name }}</option>
            @endforeach
        </select>
    </div>
    --}}

    <div class="col-md-3 mb-3">
        <label class="form-label">Low Stock Alert Qty</label>
        <input type="number" step="0.0001" min="0" name="minimum_stock" class="form-control form-control-sm" placeholder="e.g. 5" value="{{ old('minimum_stock', $item->minimum_stock ?? '') }}">
        <div class="form-text">Stock at or below this quantity is flagged in the Low Stock Report. Leave blank to disable.</div>
    </div>

    @unless(isset($item))
        <div class="col-md-3 mb-3">
            <label class="form-label">Opening Stock</label>
            <input type="number" step="0.0001" name="opening_stock" class="form-control form-control-sm" value="{{ old('opening_stock', 0) }}">
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">Opening Value (Total)</label>
            <input type="number" step="0.01" name="opening_value" class="form-control form-control-sm" value="{{ old('opening_value', 0) }}">
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">Opening Store</label>
            <select name="opening_store_id" class="form-control form-control-sm inv-select2">
                <option value="">— None —</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}" @selected(old('opening_store_id') == $store->id)>{{ $store->name }}</option>
                @endforeach
            </select>
        </div>
    @endunless

    @if(isset($item))
        @if(!$item->opening_store_id || auth()->user()->can('inv_item.change_store'))
            <div class="col-md-3 mb-3">
                <label class="form-label">Store</label>
                <select name="opening_store_id" class="form-control form-control-sm inv-select2">
                    @unless($item->opening_store_id)<option value="">— None —</option>@endunless
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}" @selected(old('opening_store_id', $item->opening_store_id) == $store->id)>{{ $store->name }}</option>
                    @endforeach
                </select>
                <div class="form-text">
                    @if($item->opening_store_id)
                        Changing the store moves this item's whole history into the new store — every report (old dates too) shows its stock there. Quantity and value don't change.
                    @else
                        This item has no store assigned yet. Set it here — any existing history moves into it.
                    @endif
                </div>
            </div>
        @else
            <div class="col-md-3 mb-3">
                <label class="form-label">Store</label>
                <input type="text" class="form-control form-control-sm" value="{{ $item->openingStore?->name }}" disabled>
            </div>
        @endif
    @endif

    @can('inv_barcode.use')
        <div class="col-md-3 mb-3">
            <div class="custom-control custom-switch mt-4">
                <input type="hidden" name="barcode_enabled" value="0">
                <input type="checkbox" name="barcode_enabled" value="1" class="custom-control-input" id="itemBarcodeEnabled{{ $item->id ?? 'new' }}"
                    @checked(old('barcode_enabled', $item->barcode_enabled ?? false))>
                <label class="custom-control-label" for="itemBarcodeEnabled{{ $item->id ?? 'new' }}">Enable Barcode</label>
            </div>
            <div class="form-text">Leave off for very small/loose items that don't need one.</div>
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">Barcode</label>
            <input type="text" name="barcode" class="form-control form-control-sm" value="{{ old('barcode', $item->barcode ?? '') }}" placeholder="Generate from the item list, or type an existing code">
        </div>
    @endcan

    <div class="col-12 mb-3">
        <label class="form-label">Specification</label>
        <textarea name="specification" class="form-control form-control-sm" rows="1">{{ old('specification', $item->specification ?? '') }}</textarea>
    </div>

    <div class="col-12">
        <div class="custom-control custom-switch mb-4">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="custom-control-input" id="itemActive"
                @checked(old('is_active', $item->is_active ?? true))>
            <label class="custom-control-label" for="itemActive">Active</label>
        </div>
    </div>
</div>
