@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Store Overview') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

<style>
.store-grid-card { position: relative; border: 1px solid #ececec; border-radius: 10px; padding: 10px 8px 8px;
    background: #fff; min-height: 66px; display: flex; flex-direction: column; justify-content: space-between;
    box-shadow: 0 1px 4px rgba(0,0,0,.05); transition: box-shadow .15s ease, transform .15s ease; }
.store-grid-card:hover { box-shadow: 0 6px 16px rgba(0,0,0,.13); transform: translateY(-2px); z-index: 2; }
.store-grid-card .card-code { font-size: 11px; font-weight: 800; color: #52525b; letter-spacing: .2px; padding-right: 14px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.store-grid-card .card-dot { position: absolute; top: 8px; right: 8px; width: 10px; height: 10px; border-radius: 50%; box-shadow: 0 0 0 2px #fff; }
.store-grid-card .card-dot.active { background: #22c55e; }
.store-grid-card .card-dot.empty { background: #eab308; }
.store-grid-card .card-dot.inactive { background: #9ca3af; }
.store-grid-card .card-qty { align-self: flex-end; font-size: 13.5px; font-weight: 800; color: #818488; }
.store-grid-card .card-qty-active { align-self: flex-end; font-size: 13.5px; font-weight: 800; color: #007bff; }
.store-card-grid { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); gap: 10px; margin-bottom: 4px; }
@media (max-width: 1500px) { .store-card-grid { grid-template-columns: repeat(8, minmax(0, 1fr)); } }
@media (max-width: 1100px) { .store-card-grid { grid-template-columns: repeat(6, minmax(0, 1fr)); } }
@media (max-width: 768px)  { .store-card-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
@media (max-width: 480px)  { .store-card-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
.status-indicator { display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 700; padding: 5px 12px; border-radius: 999px; background: #f4f4f5; margin-left: 6px; }
.status-indicator .dot { width: 9px; height: 9px; border-radius: 50%; }
.status-indicator .dot.active { background: #22c55e; }
.status-indicator .dot.empty { background: #eab308; }
.status-indicator .dot.inactive { background: #9ca3af; }
.category-section-title { font-weight: 800; color: #9a3412; background: #fff8ed; padding: 6px 12px; border-radius: 8px; margin: 18px 0 10px; font-size: 13px; text-transform: uppercase; letter-spacing: .3px; display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
.category-section-title:first-child { margin-top: 0; }
.category-section-title .group-counts { display: flex; gap: 6px; text-transform: none; letter-spacing: 0; }
.category-section-title .group-counts .status-indicator { padding: 3px 10px; font-size: 11.5px; background: #fff; }
</style>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0">Store Overview — {{ $store?->name ?? 'All Stores' }}</h5>
            <div>
                <span class="status-indicator"><span class="dot active"></span> Stocked: {{ $counts['active'] }}</span>
                <span class="status-indicator"><span class="dot empty"></span> Empty: {{ $counts['empty'] }}</span>
                <span class="status-indicator"><span class="dot inactive"></span> Inactive: {{ $counts['inactive'] }}</span>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-4">
                <div class="col-md-2">
                    <input type="text" name="search" class="form-control" placeholder="Search item code or name" value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="store_id" class="form-control inv-select2">
                        <option value="" @selected(! $store)>All Stores</option>
                        @foreach($stores as $s)
                            <option value="{{ $s->id }}" @selected($store?->id == $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="category_id" class="form-control inv-select2">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary w-100">Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('inventory.stock-overview.cards') }}" class="btn btn-light w-100">Reset</a>
                </div>
            </form>

                @forelse($grouped as $categoryName => $categoryItems)
                    <div class="category-section-title">
                        <span>{{ $categoryName }} ({{ $categoryItems->count() }})</span>
                        <span class="group-counts">
                            <span class="status-indicator"><span class="dot active"></span> Stocked: {{ $groupCounts[$categoryName]['active'] }}</span>
                            <span class="status-indicator"><span class="dot empty"></span> Empty: {{ $groupCounts[$categoryName]['empty'] }}</span>
                            <span class="status-indicator"><span class="dot inactive"></span> Inactive: {{ $groupCounts[$categoryName]['inactive'] }}</span>
                        </span>
                    </div>
                    <div class="store-card-grid">
                        @foreach($categoryItems as $item)
                            @php $status = $statusOf($item); $qty = (float) ($balances[$item->id] ?? 0); @endphp
                            <div class="store-grid-card" title="{{ $item->item_name }}{{ $store ? '' : ' — ' . ($item->openingStore?->name ?? 'No store') }}">
                                <span class="card-dot {{ $status }}"></span>
                                <div class="card-code">{{ $item->item_code }}</div>
                                @if($qty > 0)
                                    <div class="card-qty-active">{{ inv_qty($qty) }}</div>
                                @else
                                    <div class="card-qty">{{ inv_qty($qty) ?: '0' }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @empty
                    <p class="text-muted text-center py-4">No items found.</p>
                @endforelse
        </div>
    </div>
</div>

@include('sfl-inventory::admin.partials.select2-init')
@endsection
