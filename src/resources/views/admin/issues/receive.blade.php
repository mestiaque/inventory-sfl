@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Confirm Receipt') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Confirm Department Receipt — {{ $issue->issue_no }}</h5>
            <a href="{{ route('inventory.issues.index') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
            <p><strong>Department:</strong> {{ $issue->department?->name }} &nbsp;|&nbsp; <strong>Issue Date:</strong> {{ $issue->issue_date?->format('d M Y') }}</p>

            <form method="POST" action="{{ route('inventory.issues.receive', $issue) }}">
                @csrf
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle">
                        <thead><tr><th>Item</th><th>Issued Qty</th><th style="width:180px">Received Qty</th></tr></thead>
                        <tbody>
                            @foreach($issue->items as $item)
                                <tr>
                                    <td>{{ $item->item?->item_code }} — {{ $item->item?->item_name }}</td>
                                    <td>{{ $item->issued_qty }}</td>
                                    <td>
                                        <input type="hidden" name="items[{{ $loop->index }}][id]" value="{{ $item->id }}">
                                        <input type="number" step="0.0001" min="0" max="{{ $item->issued_qty }}" class="form-control"
                                            name="items[{{ $loop->index }}][department_received_qty]"
                                            value="{{ $item->department_received_qty > 0 ? $item->department_received_qty : $item->issued_qty }}">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mb-3">
                    <label class="form-label">Remarks</label>
                    <textarea name="department_receive_remarks" class="form-control" rows="2">{{ $issue->department_receive_remarks }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary">Confirm Receipt</button>
                <a href="{{ route('inventory.issues.index') }}" class="btn btn-light">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
