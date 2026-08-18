@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Add GRN') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Add GRN — Choose Challan Type</h5>
            <a href="{{ route('inventory.grns.index') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <a href="{{ route('inventory.grns.create-purchase') }}" class="text-decoration-none">
                        <div class="card h-100 border-primary">
                            <div class="card-body text-center py-5">
                                <i class="fa-solid fa-file-invoice fa-2x text-primary mb-3"></i>
                                <h6 class="mb-1">Purchase Challan</h6>
                                <p class="text-muted mb-0" style="font-size:13px;">Receive against an approved Store Order from a Supplier.</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="{{ route('inventory.grns.create-buyer') }}" class="text-decoration-none">
                        <div class="card h-100 border-success">
                            <div class="card-body text-center py-5">
                                <i class="fa-solid fa-truck-ramp-box fa-2x text-success mb-3"></i>
                                <h6 class="mb-1">Buyer Supplied Challan</h6>
                                <p class="text-muted mb-0" style="font-size:13px;">Fabric/accessories the buyer sends directly — no purchase, no supplier.</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
