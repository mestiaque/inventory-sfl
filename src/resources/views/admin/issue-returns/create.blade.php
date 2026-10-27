@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Return to Store') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Return to Store — {{ $issue->issue_no }}</h4>
            <a href="{{ route('inventory.issues.index') }}" class="btn btn-sm ui-act ui-act-back" title="Back"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
            <p>
                <strong>Department:</strong> {{ $issue->department?->name }} &nbsp;|&nbsp;
                <strong>Issued From:</strong> {{ $issue->store?->name }} &nbsp;|&nbsp;
                <strong>Issue Date:</strong> {{ $issue->issue_date?->format('d M Y') }}
                @if($issue->toStore)
                    &nbsp;|&nbsp; <strong>Floor Store:</strong> {{ $issue->toStore->name }}
                @endif
            </p>
            <p class="text-muted small mb-3">
                Returned goods go back into <strong>{{ $issue->store?->name }}</strong> at the rate they were issued at.
                @if($issue->toStore) They are taken out of <strong>{{ $issue->toStore->name }}</strong> first. @endif
            </p>

            <form method="POST" action="{{ route('inventory.issue-returns.store', $issue) }}">
                @csrf
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Return Date <span class="text-danger">*</span></label>
                        <input type="date" name="return_date" class="form-control form-control-sm" value="{{ old('return_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-9 mb-3">
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <input type="text" name="reason" class="form-control form-control-sm" value="{{ old('reason') }}" placeholder="Why is it coming back? e.g. extra quantity, order changed" required>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle">
                        <thead>
                            <tr><th>Item</th><th>Color</th><th>Size</th><th>UOM</th><th class="text-right">Issued</th><th class="text-right">Already Returned</th><th class="text-right">Can Return</th><th style="width:170px">Return Qty</th></tr>
                        </thead>
                        <tbody>
                            @foreach($issue->items as $line)
                                @php
                                    $done = $returned[$line->id] ?? 0;
                                    $left = max(0, (float) $line->issued_qty - $done);
                                @endphp
                                <tr>
                                    <td>{{ $line->item?->item_code }} — {{ $line->item?->item_name }}</td>
                                    <td>{{ $line->color?->name ?? '—' }}</td>
                                    <td>{{ $line->size?->name ?? '—' }}</td>
                                    <td>{{ $line->item?->unit?->short_name }}</td>
                                    <td class="text-right">{{ inv_qty($line->issued_qty) }}</td>
                                    <td class="text-right">{{ $done > 0 ? inv_qty($done) : '—' }}</td>
                                    <td class="text-right">{{ inv_qty($left) }}</td>
                                    <td>
                                        <input type="hidden" name="items[{{ $loop->index }}][issue_item_id]" value="{{ $line->id }}">
                                        <input type="number" step="0.0001" min="0" max="{{ $left }}" class="form-control form-control-sm"
                                            name="items[{{ $loop->index }}][returned_qty]" value="{{ old("items.{$loop->index}.returned_qty") }}"
                                            @disabled($left <= 0) placeholder="{{ $left > 0 ? '0' : 'All returned' }}">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <button type="submit" class="btn btn-sm ui-act ui-act-save" title="Save Return" onclick="return confirm('Put these quantities back into {{ $issue->store?->name }}?')"><i class="fa-solid fa-floppy-disk"></i> Save Return</button>
                <a href="{{ route('inventory.issues.index') }}" class="btn btn-sm ui-act ui-act-cancel" title="Cancel"><i class="fa-solid fa-ban"></i> Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
