@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Data Conflicts') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card mb-3">
        <div class="card-header"><h4 class="mb-0">Data Conflicts</h4></div>
        <div class="card-body">
            <p class="text-muted small mb-2">
                Old records that break today's rules. New documents can no longer create these: every item must have a store and can only be
                purchased, received, requisitioned and issued in that store, and Buyer Store issues can't exceed what was received under the same buyer + style.
            </p>
            <div class="d-flex flex-wrap gap-2">
                <a href="#stray" class="btn btn-sm {{ $strayStock->isEmpty() ? 'btn-outline-success' : 'btn-outline-danger' }}">Stock outside own store: {{ $strayStock->count() }}</a>
                <a href="#nostore" class="btn btn-sm {{ $noStore->isEmpty() ? 'btn-outline-success' : 'btn-outline-danger' }}">Items with stock but no store: {{ $noStore->count() }}</a>
                <a href="#wrongdocs" class="btn btn-sm {{ $wrongStoreDocs->isEmpty() ? 'btn-outline-success' : 'btn-outline-warning' }}">Documents in wrong store: {{ $wrongStoreDocs->count() }}</a>
                <a href="#style" class="btn btn-sm {{ $styleOverIssues->isEmpty() ? 'btn-outline-success' : 'btn-outline-warning' }}">Style over-issues: {{ $styleOverIssues->count() }}</a>
            </div>
        </div>
    </div>

    <div class="card mb-3" id="stray">
        <div class="card-header"><h5 class="mb-0">1. Stock sitting outside the item's own store</h5></div>
        <div class="card-body">
            <p class="text-muted small">Reports show this stock under the wrong store. <strong>Move into own store</strong> transfers it (a paired store-change entry, history kept).</p>
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle mb-0">
                    <thead><tr><th>Item</th><th>Stock is in</th><th>Item's own store</th><th class="text-right">Balance</th><th class="text-center">Action</th></tr></thead>
                    <tbody>
                        @forelse($strayStock as $row)
                            <tr>
                                <td>{{ $row->item_code }} — {{ $row->item_name }}</td>
                                <td>{{ $row->store_name }}</td>
                                <td>{{ $row->own_store }}</td>
                                <td class="text-right {{ $row->balance < 0 ? 'text-danger' : '' }}">{{ inv_qty($row->balance) }}</td>
                                <td class="text-center">
                                    @can('inv_negative_stock.fix')
                                        <form method="POST" action="{{ route('inventory.data-conflicts.consolidate', $row->item_id) }}" onsubmit="return confirm('Move {{ inv_qty($row->balance) }} of {{ $row->item_code }} from {{ $row->store_name }} into {{ $row->own_store }}?');">
                                            @csrf
                                            <button class="btn btn-primary btn-sm">Move into own store</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-success">None.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-3" id="nostore">
        <div class="card-header"><h5 class="mb-0">2. Items with stock movement but no store</h5></div>
        <div class="card-body">
            <p class="text-muted small">These no longer appear in Purchase, Store Order, GRN, Requisition or Issue. Set a store in Item Master to use them again — any stock in other stores moves into it automatically.</p>
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle mb-0">
                    <thead><tr><th>Item</th><th>Has movement in</th><th class="text-right">Balance (all stores)</th><th class="text-center">Action</th></tr></thead>
                    <tbody>
                        @forelse($noStore as $row)
                            <tr>
                                <td>{{ $row->item_code }} — {{ $row->item_name }}</td>
                                <td>{{ $row->stores }}</td>
                                <td class="text-right {{ $row->balance < 0 ? 'text-danger' : '' }}">{{ inv_qty($row->balance) }}</td>
                                <td class="text-center">
                                    @can('inv_item.edit')
                                        <a href="{{ route('inventory.items.edit', $row->item_id) }}" class="btn btn-outline-primary btn-sm">Set store</a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-success">None.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-3" id="wrongdocs">
        <div class="card-header"><h5 class="mb-0">3. Documents posted against a store that isn't the item's own</h5></div>
        <div class="card-body">
            <p class="text-muted small">History only. A wrong-store Issue can be moved from <a href="{{ route('inventory.negative-stock.index') }}">Negative Stock Fix</a>; leftover stock can be moved with section 1.</p>
            <div class="table-responsive" style="max-height:480px; overflow:auto">
                <table class="table table-bordered table-sm align-middle mb-0">
                    <thead><tr><th>Type</th><th>Document</th><th>Date</th><th>Item</th><th class="text-right">Qty</th><th>Posted in</th><th>Item's own store</th></tr></thead>
                    <tbody>
                        @forelse($wrongStoreDocs as $row)
                            <tr>
                                <td>{{ $row->doc_type }}</td>
                                <td>{{ $row->doc_no }}</td>
                                <td>{{ \Carbon\Carbon::parse($row->doc_date)->format('d-m-Y') }}</td>
                                <td>{{ $row->item_code }} — {{ $row->item_name }}</td>
                                <td class="text-right">{{ inv_qty($row->qty) }}</td>
                                <td>{{ $row->store_name }}</td>
                                <td>{{ $row->own_store }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-success">None.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-3" id="style">
        <div class="card-header"><h5 class="mb-0">4. Buyer Store: issued more under a style than was received under it</h5></div>
        <div class="card-body">
            <p class="text-muted small">Usually stock received under one style was issued against another, or issued with no style. New issues are now blocked from doing this.</p>
            <div class="table-responsive" style="max-height:480px; overflow:auto">
                <table class="table table-bordered table-sm align-middle mb-0">
                    <thead><tr><th>Style</th><th>Buyer</th><th>Item</th><th class="text-right">Received</th><th class="text-right">Issued</th><th class="text-right">Over by</th><th>Challans</th></tr></thead>
                    <tbody>
                        @forelse($styleOverIssues as $row)
                            <tr>
                                <td>{{ $row->style ?: '(no style)' }}</td>
                                <td>{{ $row->buyer_name ?? '—' }}</td>
                                <td>{{ $row->item_code }} — {{ $row->item_name }}</td>
                                <td class="text-right">{{ inv_qty($row->received) }}</td>
                                <td class="text-right">{{ inv_qty($row->issued) }}</td>
                                <td class="text-right text-danger">{{ inv_qty($row->issued - $row->received) }}</td>
                                <td class="small">{{ $row->issue_nos }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-success">None.</td></tr>
                        @endforelse
                    </tbody>
                    @if($styleOverIssues->isNotEmpty())
                        <tfoot>
                            <tr class="font-weight-bold">
                                <td colspan="3" class="text-right">Total</td>
                                <td class="text-right">{{ inv_qty($styleOverIssues->sum('received')) }}</td>
                                <td class="text-right">{{ inv_qty($styleOverIssues->sum('issued')) }}</td>
                                <td class="text-right text-danger">{{ inv_qty($styleOverIssues->sum(fn ($r) => $r->issued - $r->received)) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
