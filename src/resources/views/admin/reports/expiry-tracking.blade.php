@php $printMode = $printMode ?? request()->boolean('print'); @endphp
@extends(request()->boolean('excel_export') ? 'sfl-inventory::export-minimal' : ($printMode ? 'printMaster2' : adminTheme() . 'layouts.app'))

@section('title')
    @if($printMode)
        {{ websiteTitle('Expiry Tracking Report') }}
    @else
        <title>{{ websiteTitle('Expiry Tracking Report') }}</title>
    @endif
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @if($printMode)
        @include('sfl-inventory::admin.reports.partials.print-header', ['title' => 'Expiry Tracking Report'])
    @else
        @include('sfl-inventory::admin.partials.alerts')
        @include('sfl-inventory::admin.partials.ui-kit')
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Expiry Tracking Report</h5>
            @unless($printMode)
                @include('sfl-inventory::admin.reports.partials.export-print-buttons', ['report' => 'expiry-tracking'])
            @endunless
        </div>
        <div class="card-body">
            @unless($printMode)
                <form method="GET" class="row g-2 mb-3">
                    <div class="col-md-3">
                        <select name="item_id" class="form-control inv-select2">
                            <option value="">All Items</option>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}" @selected(request('item_id') == $item->id)>{{ $item->item_code }} — {{ $item->item_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="store_id" class="form-control inv-select2">
                            <option value="">All Stores</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" @selected(request('store_id') == $store->id)>{{ $store->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-control inv-select2">
                            <option value="">All Status</option>
                            <option value="expired" @selected(request('status') === 'expired')>Expired</option>
                            <option value="expiring_soon" @selected(request('status') === 'expiring_soon')>Expiring Soon</option>
                            <option value="ok" @selected(request('status') === 'ok')>OK</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="number" min="1" name="within_days" class="form-control" placeholder="Within days" value="{{ $withinDays }}">
                        <div class="form-text">"Expiring Soon" window</div>
                    </div>
                    <div class="col-md-1"><button type="submit" class="btn btn-secondary w-100">Filter</button></div>
                </form>
            @endunless

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Item Code</th><th>Item Name</th><th>Store</th><th>GRN No</th>
                            <th>Lot No</th><th>Batch No</th><th class="text-end">Qty</th>
                            <th>Expiry Date</th><th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lines as $line)
                            @php
                                $daysLeft = now()->startOfDay()->diffInDays($line->expiry_date->startOfDay(), false);
                                if ($daysLeft < 0) {
                                    $status = 'Expired'; $badge = 'danger';
                                } elseif ($daysLeft <= $withinDays) {
                                    $status = 'Expiring Soon'; $badge = 'warning';
                                } else {
                                    $status = 'OK'; $badge = 'success';
                                }
                            @endphp
                            <tr>
                                <td>{{ $line->item?->item_code }}</td>
                                <td>{{ $line->item?->item_name }}</td>
                                <td>{{ $line->grn?->store?->name }}</td>
                                <td>{{ $line->grn?->grn_number }}</td>
                                <td>{{ $line->lot_no ?? '—' }}</td>
                                <td>{{ $line->batch_no ?? '—' }}</td>
                                <td class="text-end">{{ inv_qty($line->received_qty) }} {{ $line->item?->unit?->short_name }}</td>
                                <td>{{ $line->expiry_date->format('d M Y') }}</td>
                                <td>
                                    <span class="badge p-1 text-white bg-{{ $badge }}">
                                        {{ $status }}{{ $daysLeft >= 0 ? ' (' . $daysLeft . 'd)' : '' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted">No items with an expiry date recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @unless($printMode)
                {{ $lines->links('pagination::bootstrap-5') }}
            @endunless
        </div>
    </div>
</div>
@unless($printMode)
    @include('sfl-inventory::admin.partials.select2-init')
@endunless
@endsection
