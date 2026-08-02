@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Approve Requisition') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Approve / Reject — {{ $requisition->requisition_no }}</h5>
            <a href="{{ route('inventory.requisitions.index') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
            <p>
                <strong>Department:</strong> {{ $requisition->department?->name }} &nbsp;|&nbsp;
                <strong>Store:</strong> {{ $requisition->store?->name }} &nbsp;|&nbsp;
                <strong>Date:</strong> {{ $requisition->requisition_date?->format('d M Y') }}
                @if($requisition->buyer || $requisition->style)
                    <br><strong>Buyer:</strong> {{ $requisition->buyer?->name ?? '—' }} &nbsp;|&nbsp;
                    <strong>Style:</strong> {{ $requisition->style ?? '—' }}
                    @if($requisition->order_ref) &nbsp;|&nbsp; <strong>Order Ref:</strong> {{ $requisition->order_ref }} @endif
                @endif
            </p>

            <form method="POST" action="{{ route('inventory.requisitions.approval', $requisition) }}">
                @csrf
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle">
                        <thead><tr><th>Item</th><th>Requested Qty</th><th style="width:180px">Approved Qty</th></tr></thead>
                        <tbody>
                            @foreach($requisition->items as $item)
                                <tr>
                                    <td>{{ $item->item?->item_code }} — {{ $item->item?->item_name }}</td>
                                    <td>{{ $item->requested_qty }}</td>
                                    <td>
                                        <input type="hidden" name="items[{{ $loop->index }}][id]" value="{{ $item->id }}">
                                        <input type="number" step="0.0001" min="0" class="form-control"
                                            name="items[{{ $loop->index }}][approved_qty]" value="{{ $item->requested_qty }}">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mb-3">
                    <label class="form-label">Remarks</label>
                    <textarea name="approval_remarks" class="form-control" rows="2"></textarea>
                </div>

                <button type="submit" name="decision" value="approve" class="btn btn-success">Approve</button>
                <button type="submit" name="decision" value="reject" class="btn btn-danger" onclick="return confirm('Reject this requisition?')">Reject</button>
                <a href="{{ route('inventory.requisitions.index') }}" class="btn btn-light">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
