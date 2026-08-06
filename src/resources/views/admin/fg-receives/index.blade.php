@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Finished Goods Receive') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Finished Goods Receive</h5>
            @can('inv_fg_receive.add')
                <a href="{{ route('inventory.fg-receives.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Add Receive</a>
            @endcan
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-2">
                    <input type="text" name="search" class="form-control" placeholder="Search receive no" value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="buyer_id" class="form-control inv-select2">
                        <option value="">All Buyers</option>
                        @foreach($buyers as $buyer)
                            <option value="{{ $buyer->id }}" @selected(request('buyer_id') == $buyer->id)>{{ $buyer->name }}</option>
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
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="From">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="To">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-secondary w-100">Filter</button>
                </div>
                <div class="col-md-1">
                    <a href="{{ route('inventory.fg-receives.index') }}" class="btn btn-light w-100">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr><th>#</th><th>Receive No</th><th>Style</th><th>Buyer</th><th>Order Ref</th><th>Store</th><th>Date</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($receives as $receive)
                            <tr>
                                <td>{{ $loop->iteration + $receives->firstItem() - 1 }}</td>
                                <td>{{ $receive->receive_no }}</td>
                                <td>{{ $receive->style }}</td>
                                <td>{{ $receive->buyer?->name }}</td>
                                <td>{{ $receive->order_ref }}</td>
                                <td>{{ $receive->store?->name }}</td>
                                <td>{{ $receive->receive_date?->format('d M Y') }}</td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-toggle="modal" data-target="#viewFgrModal{{ $receive->id }}">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted">No records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $receives->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

{{-- View modals live outside the table (a <div> can't legally be a direct child of <tbody>, and a nested <table> inside it would get corrupted by the browser's table-repair parsing otherwise). --}}
@foreach($receives as $receive)
    <div class="modal fade" id="viewFgrModal{{ $receive->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">FG Receive Details — {{ $receive->receive_no }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <dl class="row mb-3">
                        <dt class="col-sm-3">Store</dt><dd class="col-sm-9">{{ $receive->store?->name ?? '—' }}</dd>
                        <dt class="col-sm-3">Receive Date</dt><dd class="col-sm-9">{{ $receive->receive_date?->format('d M Y') }}</dd>
                        <dt class="col-sm-3">Buyer</dt><dd class="col-sm-9">{{ $receive->buyer?->name ?? '—' }}</dd>
                        <dt class="col-sm-3">Style / Order Ref</dt><dd class="col-sm-9">{{ collect([$receive->style, $receive->order_ref])->filter()->implode(' / ') ?: '—' }}</dd>
                        <dt class="col-sm-3">Created By</dt><dd class="col-sm-9">{{ $receive->creator?->name ?? '—' }}</dd>
                        <dt class="col-sm-3">Remarks</dt><dd class="col-sm-9">{{ $receive->remarks ?: '—' }}</dd>
                    </dl>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0">
                            <thead>
                                <tr><th>#</th><th>Item</th><th>Unit</th><th class="text-end">Quantity</th></tr>
                            </thead>
                            <tbody>
                                @foreach($receive->items as $line)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $line->item?->item_code }} — {{ $line->item?->item_name }}</td>
                                        <td>{{ $line->item?->unit?->short_name ?? '—' }}</td>
                                        <td class="text-end">{{ inv_qty($line->quantity) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endforeach

@include('sfl-inventory::admin.partials.select2-init')
@endsection
