{{--
    Buyers with Merchandising installed: read-only. Buyers and their styles
    are added / changed only in Merchandising (Master Data → Buyers / Styles);
    every Inventory form picks Buyer → Style from there.
--}}
@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Buyers') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    @php
        $merBuyersUrl = \Illuminate\Support\Facades\Route::has('msfl.masters.index') ? route('msfl.masters.index', 'buyers') : null;
        $merStylesUrl = \Illuminate\Support\Facades\Route::has('msfl.masters.index') ? route('msfl.masters.index', 'styles') : null;
    @endphp

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Buyers <small class="text-muted">(Merchandising)</small></h4>
            <div>
                @if($merBuyersUrl)
                    <a href="{{ $merBuyersUrl }}" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-handshake"></i> Manage Buyers</a>
                @endif
                @if($merStylesUrl)
                    <a href="{{ $merStylesUrl }}" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-shirt"></i> Manage Styles</a>
                @endif
            </div>
        </div>
        <div class="card-body">
            <div class="alert alert-info py-2">
                Buyers and styles are kept in <strong>Merchandising → Master Data</strong> only — add, edit or remove them there.
                Inventory forms pick <strong>Buyer → Style</strong> from that list. A buyer is pickable once it is active and approved.
            </div>

            <form method="GET" class="row mb-3 align-items-end">
                <div class="col-md-3 mb-2">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name or code" value="{{ request('search') }}">
                </div>
                <div class="col-md-3 mb-2">
                    <select name="status" class="form-control form-control-sm inv-select2">
                        <option value="">All Status</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2 d-flex align-items-end flex-wrap gap-1">
                    <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
                    <a href="{{ route('inventory.buyers.index') }}" class="btn btn-light btn-sm">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle">
                    <thead>
                        <tr><th>#</th><th>Name</th><th>Code</th><th>Contact</th><th>Approval</th><th>Status</th><th>Styles</th></tr>
                    </thead>
                    <tbody>
                        @forelse($buyers as $buyer)
                            @php
                                $buyerStyles = $styles->get($buyer->id, collect());
                                $buyerLegacy = $legacyStyles->get($buyer->id, collect());
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration + $buyers->firstItem() - 1 }}</td>
                                <td>{{ $buyer->name }}</td>
                                <td>{{ $buyer->code }}</td>
                                <td>{{ collect([$buyer->contact_person, $buyer->phone])->filter()->implode(' / ') ?: '—' }}</td>
                                <td>{{ ucfirst((string) $buyer->approval_status) ?: '—' }}</td>
                                <td>
                                    <span class="badge badge-{{ $buyer->is_active ? 'success' : 'secondary' }}">{{ $buyer->is_active ? 'Active' : 'Inactive' }}</span>
                                </td>
                                <td>
                                    @if($buyerStyles->isEmpty() && $buyerLegacy->isEmpty())
                                        <span class="text-muted">—</span>
                                    @else
                                        <button type="button" class="btn btn-link btn-sm p-0" data-toggle="modal" data-target="#buyerStylesModal{{ $buyer->id }}">
                                            {{ $buyerStyles->count() }} Merchandising{{ $buyerLegacy->isNotEmpty() ? ' + ' . $buyerLegacy->count() . ' Inventory' : '' }}
                                        </button>
                                    @endif
                                </td>
                            </tr>
                            @if($buyerStyles->isNotEmpty() || $buyerLegacy->isNotEmpty())
                                <div class="modal fade" id="buyerStylesModal{{ $buyer->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Styles — {{ $buyer->name }}</h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                            </div>
                                            <div class="modal-body">
                                                <table class="table table-bordered table-sm mb-0">
                                                    <thead><tr><th>Style No</th><th>Name</th><th>From</th></tr></thead>
                                                    <tbody>
                                                        @foreach($buyerStyles as $s)
                                                            <tr>
                                                                <td>{{ $s->style_no }}</td>
                                                                <td>{{ $s->name }} @unless($s->is_active)<span class="badge badge-secondary">Inactive</span>@endunless</td>
                                                                <td>Merchandising</td>
                                                            </tr>
                                                        @endforeach
                                                        @foreach($buyerLegacy as $s)
                                                            <tr><td>{{ $s->style_no }}</td><td class="text-muted">—</td><td>Inventory (earlier receives)</td></tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">No buyers found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $buyers->links('pagination::bootstrap-5') }}
        </div>
    </div>

    @if($inventoryOnly->isNotEmpty())
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">Older Inventory buyers <small class="text-muted">— not in Merchandising, kept on earlier records only</small></h6>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-2">These stay on the documents that already use them but can't be picked on new ones. To use one again, add a buyer with the same name in Merchandising → Master Data → Buyers — it is matched by name.</p>
                <table class="table table-bordered table-sm mb-0">
                    <thead><tr><th>Name</th><th>Code</th><th>Contact</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach($inventoryOnly as $buyer)
                            <tr>
                                <td>{{ $buyer->name }}</td>
                                <td>{{ $buyer->code }}</td>
                                <td>{{ $buyer->contact ?: '—' }}</td>
                                <td><span class="badge badge-{{ $buyer->is_active ? 'success' : 'secondary' }}">{{ $buyer->is_active ? 'Active' : 'Inactive' }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@include('sfl-inventory::admin.partials.select2-init')
@endsection
