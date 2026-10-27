@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Add Purchase Requisition') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Add Purchase Requisition</h4>
            <a href="{{ route('inventory.purchase-requisitions.index') }}" class="btn btn-sm ui-act ui-act-back" title="Back"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('inventory.purchase-requisitions.store') }}">
                @csrf
                @include('sfl-inventory::admin.purchase-requisitions.partials.form')

                {{-- Auto-approve option temporarily disabled — every purchase requisition should go
                     through the normal single approval step.
                @can('inv_purchase_requisition.approve')
                    <div class="form-check mt-3">
                        <input type="checkbox" name="auto_approve" value="1" class="form-check-input" id="autoApprove" @checked(old('auto_approve'))>
                        <label class="form-check-label" for="autoApprove">Auto-approve this purchase requisition</label>
                        <div class="form-text">Skips the separate approval step — the requisition is created already approved, ready to convert to a Purchase Order.</div>
                    </div>
                @endcan
                --}}

                <button type="submit" class="btn mt-3 btn-sm ui-act ui-act-save" title="Submit Purchase Requisition"><i class="fa-solid fa-floppy-disk"></i> Submit Purchase Requisition</button>
                <a href="{{ route('inventory.purchase-requisitions.index') }}" class="btn mt-3 btn-sm ui-act ui-act-cancel" title="Cancel"><i class="fa-solid fa-ban"></i> Cancel</a>
            </form>
        </div>
    </div>
</div>

@include('sfl-inventory::admin.partials.select2-init')
@include('sfl-inventory::admin.partials.line-items-script')
@endsection
