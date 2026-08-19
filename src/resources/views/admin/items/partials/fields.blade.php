{{-- props: item (optional, for edit), categories, units, brands, colors, sizes, stores --}}
<div class="row">
    <div class="col-md-3 mb-3">
        <label class="form-label">Item Code <span class="text-danger">*</span></label>
        <input type="text" name="item_code" class="form-control" value="{{ old('item_code', $item->item_code ?? '') }}" placeholder="e.g. SFL-SW-090" required>
        <div class="form-text">Enter the item's code yourself — it is not auto-generated.</div>
    </div>
    <div class="col-md-9 mb-3">
        <label class="form-label">Item Name <span class="text-danger">*</span></label>
        <input type="text" name="item_name" class="form-control" value="{{ old('item_name', $item->item_name ?? '') }}" required>
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label">Category <span class="text-danger">*</span></label>
        <select name="category_id" class="form-control inv-select2" required>
            <option value="">— Select —</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id', $item->category_id ?? '') == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Department / Section</label>
        <select name="department_id" class="form-control inv-select2">
            <option value="">— None —</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}" @selected(old('department_id', $item->department_id ?? '') == $department->id)>{{ $department->name }}</option>
            @endforeach
        </select>
        <div class="form-text">Floor section that uses this item.</div>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Supplier</label>
        <select name="supplier_id" class="form-control inv-select2">
            <option value="">— None —</option>
            @foreach($suppliers as $supplier)
                <option value="{{ $supplier->id }}" @selected(old('supplier_id', $item->supplier_id ?? '') == $supplier->id)>{{ $supplier->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Buyer</label>
        <select name="buyer_id" class="form-control inv-select2">
            <option value="">— None —</option>
            @foreach($buyers as $buyer)
                <option value="{{ $buyer->id }}" @selected(old('buyer_id', $item->buyer_id ?? '') == $buyer->id)>{{ $buyer->name }}</option>
            @endforeach
        </select>
        <div class="form-text">Only for buyer-specific items.</div>
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label">Unit <span class="text-danger">*</span></label>
        <select name="unit_id" class="form-control inv-select2" required>
            <option value="">— Select —</option>
            @foreach($units as $unit)
                <option value="{{ $unit->id }}" @selected(old('unit_id', $item->unit_id ?? '') == $unit->id)>{{ $unit->name }} ({{ $unit->short_name }})</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Brand</label>
        <select name="brand_id" class="form-control inv-select2">
            <option value="">— None —</option>
            @foreach($brands as $brand)
                <option value="{{ $brand->id }}" @selected(old('brand_id', $item->brand_id ?? '') == $brand->id)>{{ $brand->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Color</label>
        <select name="color_id" class="form-control inv-select2">
            <option value="">— None —</option>
            @foreach($colors as $color)
                <option value="{{ $color->id }}" @selected(old('color_id', $item->color_id ?? '') == $color->id)>{{ $color->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Size</label>
        <select name="size_id" class="form-control inv-select2">
            <option value="">— None —</option>
            @foreach($sizes as $size)
                <option value="{{ $size->id }}" @selected(old('size_id', $item->size_id ?? '') == $size->id)>{{ $size->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">Low Stock Alert Qty</label>
        <input type="number" step="0.0001" min="0" name="minimum_stock" class="form-control" placeholder="e.g. 5" value="{{ old('minimum_stock', $item->minimum_stock ?? '') }}">
        <div class="form-text">Stock at or below this quantity is flagged in the Low Stock Report. Leave blank to disable.</div>
    </div>

    @unless(isset($item))
        <div class="col-md-4 mb-3">
            <label class="form-label">Opening Stock</label>
            <input type="number" step="0.0001" name="opening_stock" class="form-control" value="{{ old('opening_stock', 0) }}">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Opening Value (Total)</label>
            <input type="number" step="0.01" name="opening_value" class="form-control" value="{{ old('opening_value', 0) }}">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Opening Store</label>
            <select name="opening_store_id" class="form-control inv-select2">
                <option value="">— None —</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}" @selected(old('opening_store_id') == $store->id)>{{ $store->name }}</option>
                @endforeach
            </select>
        </div>
    @endunless

    @if(isset($item) && !$item->opening_store_id)
        <div class="col-md-4 mb-3">
            <label class="form-label">Store</label>
            <select name="opening_store_id" class="form-control inv-select2">
                <option value="">— None —</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}" @selected(old('opening_store_id') == $store->id)>{{ $store->name }}</option>
                @endforeach
            </select>
            <div class="form-text">This item has no store assigned yet. Set it here.</div>
        </div>
    @endif

    @can('inv_barcode.use')
        <div class="col-md-4 mb-3">
            <div class="form-check form-switch mt-4">
                <input type="hidden" name="barcode_enabled" value="0">
                <input type="checkbox" name="barcode_enabled" value="1" class="form-check-input" id="itemBarcodeEnabled{{ $item->id ?? 'new' }}"
                    @checked(old('barcode_enabled', $item->barcode_enabled ?? false))>
                <label class="form-check-label" for="itemBarcodeEnabled{{ $item->id ?? 'new' }}">Enable Barcode</label>
            </div>
            <div class="form-text">Leave off for very small/loose items that don't need one.</div>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Barcode</label>
            <input type="text" name="barcode" class="form-control" value="{{ old('barcode', $item->barcode ?? '') }}" placeholder="Generate from the item list, or type an existing code">
        </div>
    @endcan

    <div class="col-12 mb-3">
        <label class="form-label">Specification</label>
        <textarea name="specification" class="form-control" rows="1">{{ old('specification', $item->specification ?? '') }}</textarea>
    </div>

    <div class="col-12">
        <div class="form-check form-switch mb-4">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="itemActive"
                @checked(old('is_active', $item->is_active ?? true))>
            <label class="form-check-label" for="itemActive">Active</label>
        </div>
    </div>
</div>
