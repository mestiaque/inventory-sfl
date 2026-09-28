@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Negative Stock Fix') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    @php
        $unit = $item->unit?->short_name;
        $combo = array_filter(['item_id' => $item->id, 'store_id' => $store->id, 'color_id' => $colorId, 'size_id' => $sizeId]);
    @endphp

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Negative Stock Fix</h4>
            <a href="{{ route('inventory.negative-stock.index') }}" class="btn btn-light btn-sm">&larr; Back to list</a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-2"><div class="text-muted small">Item</div><strong>{{ $item->item_code }} — {{ $item->item_name }}</strong></div>
                <div class="col-md-2 mb-2"><div class="text-muted small">Color / Size</div><strong>{{ $color?->name ?? '—' }} / {{ $size?->name ?? '—' }}</strong></div>
                <div class="col-md-3 mb-2"><div class="text-muted small">Store</div><strong>{{ $store->name }}</strong></div>
                <div class="col-md-2 mb-2">
                    <div class="text-muted small">Current Balance</div>
                    <strong class="{{ $balance < 0 ? 'text-danger' : 'text-success' }}" style="font-size:1.2rem">{{ inv_qty($balance) }} {{ $unit }}</strong>
                </div>
                <div class="col-md-2 mb-2">
                    <div class="text-muted small">Same item in other stores</div>
                    @forelse($otherStores as $sid => $bal)
                        <div class="small">{{ $storeNames->get($sid)?->name }}: <strong class="{{ $bal < 0 ? 'text-danger' : '' }}">{{ inv_qty($bal) }}</strong></div>
                    @empty
                        <div class="small text-muted">None</div>
                    @endforelse
                </div>
            </div>
            @if($balance >= 0)
                <div class="alert alert-success mb-0 mt-2">This stock is no longer negative. Nothing left to fix.</div>
            @endif
        </div>
    </div>

    @if($movedAway->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">Challans moved out of {{ $store->name }} earlier</h5></div>
            <div class="card-body">
                <p class="text-muted small">
                    If one of these moves was a mistake, <strong>Move back</strong> returns the challan to {{ $store->name }} exactly as it was
                    before — even if that makes this store negative for a moment. Undo the wrong moves first, then move the right challan.
                </p>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle mb-0">
                        <thead><tr><th>Challan</th><th>Date</th><th>Now in store</th><th>Lines</th><th class="text-center">Action</th></tr></thead>
                        <tbody>
                            @foreach($movedAway as $moved)
                                <tr>
                                    <td>{{ $moved->issue_no }}</td>
                                    <td>{{ $moved->issue_date?->format('d-m-Y') }}</td>
                                    <td>{{ $moved->store?->name }}</td>
                                    <td class="small">
                                        @foreach($moved->items as $line)
                                            <div class="{{ $line->item_id == $item->id ? 'font-weight-bold' : '' }}">{{ $line->item?->item_code }} × {{ inv_qty($line->issued_qty) }}</div>
                                        @endforeach
                                    </td>
                                    <td class="text-center">
                                        @can('inv_negative_stock.fix')
                                            <form method="POST" action="{{ route('inventory.negative-stock.undo-move', $moved) }}"
                                                  onsubmit="return confirm('Move {{ $moved->issue_no }} back to {{ $store->name }}?');">
                                                @csrf
                                                <button type="submit" class="btn btn-warning btn-sm">Move back</button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    @if($balance < 0)
        @can('inv_negative_stock.fix')
            <div class="row">
                <div class="col-lg-8 mb-3">
                    <div class="card h-100">
                        <div class="card-header"><h5 class="mb-0">Option 1 — Issue was posted from the wrong store</h5></div>
                        <div class="card-body">
                            <p class="text-muted small">
                                Moves the whole challan to the store it really came from. Stock goes back into
                                <strong>{{ $store->name }}</strong> and is taken out of the store you pick. The target store must have enough stock.
                            </p>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm align-middle mb-0">
                                    <thead>
                                        <tr><th>Challan</th><th>Date</th><th>Department</th><th>Lines</th><th style="min-width:260px">Move to store</th></tr>
                                    </thead>
                                    <tbody>
                                        @forelse($issues as $issue)
                                            <tr>
                                                <td>{{ $issue->issue_no }}</td>
                                                <td>{{ $issue->issue_date?->format('d-m-Y') ?? $issue->issue_date }}</td>
                                                <td>{{ $issue->department?->name }}</td>
                                                <td class="small">
                                                    @foreach($issue->items as $line)
                                                        <div class="{{ $line->item_id == $item->id ? 'font-weight-bold' : '' }}">
                                                            {{ $line->item?->item_code }}
                                                            @if($line->color || $line->size) ({{ $line->color?->name ?? '—' }}/{{ $line->size?->name ?? '—' }}) @endif
                                                            × {{ inv_qty($line->issued_qty) }}
                                                        </div>
                                                    @endforeach
                                                </td>
                                                <td>
                                                    <form method="POST" action="{{ route('inventory.negative-stock.move-issue', $issue) }}"
                                                          onsubmit="return confirm('Move {{ $issue->issue_no }} out of {{ $store->name }} into the selected store?');">
                                                        @csrf
                                                        <select name="target_store_id" class="form-control form-control-sm mb-1" required>
                                                            <option value="">Select store…</option>
                                                            @foreach($allStores as $s)
                                                                <option value="{{ $s->id }}">{{ $s->name }} (has {{ inv_qty($otherStores[$s->id] ?? 0) }})</option>
                                                            @endforeach
                                                        </select>
                                                        <input type="text" name="reason" class="form-control form-control-sm mb-1" placeholder="Reason (optional)">
                                                        <button type="submit" class="btn btn-primary btn-sm btn-block">Move challan</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-center text-muted">No approved issue challans from this store for this item.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 mb-3">
                    <div class="card h-100">
                        <div class="card-header"><h5 class="mb-0">Option 2 — Set balance to 0</h5></div>
                        <div class="card-body">
                            <p class="text-muted small">
                                Use this if the goods really did leave <strong>{{ $store->name }}</strong> but were never received into it.
                                Creates a Stock Adjustment of <strong>+{{ inv_qty(-$balance) }} {{ $unit }}</strong> so the balance becomes 0.
                                @cannot('inv_adjustment.approve')
                                    It will wait in Stock Adjustment for approval.
                                @endcannot
                            </p>
                            <form method="POST" action="{{ route('inventory.negative-stock.zero-out') }}"
                                  onsubmit="return confirm('Post a +{{ inv_qty(-$balance) }} adjustment to bring {{ $store->name }} to 0?');">
                                @csrf
                                @foreach($combo as $k => $v)
                                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                                @endforeach
                                <textarea name="reason" class="form-control form-control-sm mb-2" rows="2" placeholder="Reason (optional)"></textarea>
                                <button type="submit" class="btn btn-danger btn-sm btn-block">Set balance to 0</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endcan
    @endif

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Ledger — {{ $store->name }}</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle">
                    <thead>
                        <tr><th>#</th><th>Date</th><th>Type</th><th>Remarks</th><th class="text-right">In</th><th class="text-right">Out</th><th class="text-right">Rate</th><th class="text-right">Balance</th></tr>
                    </thead>
                    <tbody>
                        @php $prev = 0; @endphp
                        @foreach($ledger as $txn)
                            @php $wentNegative = $prev >= 0 && $txn->running_balance < 0; $prev = $txn->running_balance; @endphp
                            <tr class="{{ $wentNegative ? 'table-danger' : '' }}">
                                <td>{{ $txn->id }}</td>
                                <td>{{ $txn->transaction_date?->format('d-m-Y') }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $txn->transaction_type)) }}</td>
                                <td class="small">{{ $txn->remarks }}</td>
                                <td class="text-right">{{ (float) $txn->qty_in ? inv_qty($txn->qty_in) : '' }}</td>
                                <td class="text-right">{{ (float) $txn->qty_out ? inv_qty($txn->qty_out) : '' }}</td>
                                <td class="text-right">{{ inv_qty($txn->rate) }}</td>
                                <td class="text-right {{ $txn->running_balance < 0 ? 'text-danger font-weight-bold' : '' }}">{{ inv_qty($txn->running_balance) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="small text-muted">Red row = the transaction where the balance first went below zero.</div>
        </div>
    </div>
</div>
@endsection
