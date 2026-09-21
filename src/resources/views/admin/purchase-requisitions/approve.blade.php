@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Approve Purchase Requisition') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Approve / Reject — {{ $purchaseRequisition->requisition_no }}</h5>
            <a href="{{ route('inventory.purchase-requisitions.index') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
            <p>
                <strong>Department:</strong> {{ $purchaseRequisition->department?->name ?? '—' }} &nbsp;|&nbsp;
                <strong>Date:</strong> {{ $purchaseRequisition->requisition_date?->format('d M Y') }}
            </p>

            <form method="POST" action="{{ route('inventory.purchase-requisitions.approval', $purchaseRequisition) }}">
                @csrf
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <p class="text-muted mb-0" style="font-size:12px;">Adjust an Approved Qty to edit a line, remove a row you don't want to approve, or add a new item the requester didn't originally ask for.</p>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-line-items-add="preqApprove"><i class="fa-solid fa-plus"></i> Add Item</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle">
                        <thead><tr><th>Item</th><th style="width:140px">Color</th><th style="width:140px">Size</th><th>Requested Qty</th><th style="width:180px">Approved Qty</th><th style="width:40px"></th></tr></thead>
                        <tbody id="preqApproveRowsBody">
                            @foreach($purchaseRequisition->items as $item)
                                <tr>
                                    <td>{{ $item->item?->item_code }} — {{ $item->item?->item_name }}</td>
                                    <td>{{ $item->color?->name ?? '—' }}</td>
                                    <td>{{ $item->size?->name ?? '—' }}</td>
                                    <td>{{ $item->requested_qty }}</td>
                                    <td>
                                        <input type="hidden" name="items[{{ $loop->index }}][id]" value="{{ $item->id }}">
                                        <input type="number" step="0.0001" min="0" class="form-control"
                                            name="items[{{ $loop->index }}][approved_qty]" value="{{ $item->requested_qty }}">
                                    </td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger" data-line-items-remove title="Remove — don't approve this item"><i class="fa-solid fa-xmark"></i></button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <template id="preqApproveRowTemplate">
                    <tr>
                        <td>
                            <select name="items[__INDEX__][item_id]" class="form-control inv-select2" required>
                                <option value="">— Select —</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->id }}" data-color-id="{{ $item->color_id }}" data-size-id="{{ $item->size_id }}">{{ $item->item_code }} — {{ $item->item_name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select name="items[__INDEX__][color_id]" class="form-control">
                                <option value="">— Select —</option>
                                @foreach($colors as $color)
                                    <option value="{{ $color->id }}">{{ $color->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select name="items[__INDEX__][size_id]" class="form-control">
                                <option value="">— Select —</option>
                                @foreach($sizes as $size)
                                    <option value="{{ $size->id }}">{{ $size->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="text-muted">— (new) —</td>
                        <td><input type="number" step="0.0001" min="0.0001" class="form-control" name="items[__INDEX__][approved_qty]" required></td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger" data-line-items-remove><i class="fa-solid fa-xmark"></i></button></td>
                    </tr>
                </template>

                <div class="mb-3">
                    <label class="form-label">Remarks</label>
                    <textarea name="approval_remarks" class="form-control" rows="2"></textarea>
                </div>

                <button type="submit" name="decision" value="approve" class="btn btn-success">Approve</button>
                <button type="submit" name="decision" value="reject" class="btn btn-danger" onclick="return confirm('Reject this purchase requisition?')">Reject</button>
                <a href="{{ route('inventory.purchase-requisitions.index') }}" class="btn btn-light">Cancel</a>
            </form>
        </div>
    </div>
</div>
@include('sfl-inventory::admin.partials.select2-init')
@include('sfl-inventory::admin.partials.line-items-script')
@endsection
