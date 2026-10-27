@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Issue Returns') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Issue Returns</h4>
            <a href="{{ route('inventory.issues.index') }}" class="btn btn-sm ui-act ui-act-nav" title="Store Issues"><i class="fa-solid fa-dolly"></i> Store Issues</a>
        </div>
        <div class="card-body">
            <form method="GET" class="row mb-3 align-items-end">
                <div class="col-md-3 mb-2">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Return no / issue no" value="{{ request('search') }}">
                </div>
                <div class="col-md-3 mb-2">
                    <select name="store_id" class="form-control form-control-sm inv-select2">
                        <option value="">All Stores</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" @selected(request('store_id') == $store->id)>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2 mb-2">
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2 mb-2 d-flex align-items-end flex-wrap gap-1">
                    <button type="submit" class="btn btn-sm ui-act ui-act-filter" title="Filter"><i class="fa-solid fa-filter"></i> Filter</button>
                    <a href="{{ route('inventory.issue-returns.index') }}" class="btn btn-sm ui-act ui-act-reset" title="Reset"><i class="fa-solid fa-rotate-left"></i> Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle">
                    <thead>
                        <tr><th>#</th><th>Return No</th><th>Issue No</th><th>Department</th><th>Back To Store</th><th>Date</th><th class="text-right">Qty</th><th>Reason</th><th>Returned By</th><th class="text-right">Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($returns as $return)
                            <tr>
                                <td>{{ $loop->iteration + $returns->firstItem() - 1 }}</td>
                                <td>{{ $return->return_no }}</td>
                                <td>
                                    @can('inv_issue.print')
                                        <a href="{{ route('inventory.issues.print', $return->issue_id) }}" target="_blank">{{ $return->issue?->issue_no }}</a>
                                    @else
                                        {{ $return->issue?->issue_no }}
                                    @endcan
                                </td>
                                <td>{{ $return->issue?->department?->name }}</td>
                                <td>{{ $return->store?->name }}</td>
                                <td>{{ $return->return_date?->format('d M Y') }}</td>
                                <td class="text-right">{{ inv_qty($return->returned_total) }}</td>
                                <td>{{ $return->reason }}</td>
                                <td>{{ $return->returner?->name }}</td>
                                <td class="text-right">
                                    <a href="{{ route('inventory.issue-returns.print', $return) }}" target="_blank" class="btn-custom ui-act ui-act-print" title="Print"><i class="fa-solid fa-print"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center text-muted">No returns yet. Open a challan on Store Issues and use its return button.</td></tr>
                        @endforelse
                    </tbody>
                    @if($returns->total())
                        <tfoot>
                            <tr class="font-weight-bold"><td colspan="10">Total: {{ $returns->total() }} return(s){{ $returns->hasPages() ? ' (all pages)' : '' }}</td></tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            {{ $returns->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@include('sfl-inventory::admin.partials.select2-init')
@endsection
