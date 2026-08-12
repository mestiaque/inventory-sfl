@php $printMode = $printMode ?? request()->boolean('print'); @endphp
@extends(request()->boolean('excel_export') ? 'sfl-inventory::export-minimal' : ($printMode ? 'printMaster2' : adminTheme() . 'layouts.app'))

@section('title')
    @if($printMode)
        {{ websiteTitle('Daily Broken Needle Report') }}
    @else
        <title>{{ websiteTitle('Daily Broken Needle Report') }}</title>
    @endif
@endsection

@push('css')
<style>
    .needle-sheet {  padding: 16px 20px; background: #fff; }
    .needle-sheet-header { text-align: center; margin-bottom: 10px; }
    .needle-sheet-header .company-name { font-size: 26px; font-weight: 800; letter-spacing: .5px; }
    .needle-sheet-header .company-address { font-size: 14px; font-weight: 600; margin-top: 2px; }
    .needle-sheet-titlebar { display: flex; align-items: center; justify-content: center; position: relative; margin: 10px 0 12px; }
    .needle-sheet-titlebar .title { font-size: 18px; font-weight: 700; font-style: italic; }
    .needle-sheet-titlebar .date { position: absolute; right: 0; font-size: 14px; font-weight: 600; }
    .needle-form-table { border: 2px solid #000; }
    .needle-form-table th, .needle-form-table td { border: 1px solid #000 !important; vertical-align: middle; }
    .needle-form-table th { font-size: 11px; text-transform: none; background: #fff; font-weight: 700; text-align: center; }
    .needle-form-table td { font-size: 12px; height: 30px; }
    .needle-narrow { width: 42px; }
    .needle-tape-col { min-width: 220px; }
    .needle-instructions { font-size: 12px; color: #1d4ed8; margin-top: 10px; }
    .needle-instructions li { margin-bottom: 2px; }

    @media print {
        @page { margin: 0; size: landscape; }
    }
</style>
@endpush

@section('contents')
<div class="flex-grow-1 inv-module">
    @unless($printMode)
        @include('sfl-inventory::admin.partials.alerts')
        @include('sfl-inventory::admin.partials.ui-kit')

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Daily Broken Needle Report</h5>
                    <small class="text-muted">{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('inventory.broken-needles.daily-report.export', request()->query()) }}" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-file-excel"></i> Excel</a>
                    <a href="{{ url()->current() }}?{{ http_build_query(array_merge(request()->query(), ['print' => 1])) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-print"></i> Print</a>
                    <a href="{{ route('inventory.broken-needles.index') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left"></i> Entries</a>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-2">
                    <div class="col-md-3">
                        <input type="date" name="date" class="form-control" value="{{ $date }}">
                    </div>
                    <div class="col-md-3">
                        <select name="buyer_id" class="form-control inv-select2">
                            <option value="">All Buyers</option>
                            @foreach($buyers as $buyer)
                                <option value="{{ $buyer->id }}" @selected(request('buyer_id') == $buyer->id)>{{ $buyer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="line_no" class="form-control" placeholder="Line No" value="{{ request('line_no') }}">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-secondary w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>
    @endunless

    <div class="needle-sheet">
        <div class="needle-sheet-header">
            <div class="company-name">{{ config('sfl-inventory.company.name') }}</div>
            @if(config('sfl-inventory.company.address'))
                <div class="company-address">{{ config('sfl-inventory.company.address') }}</div>
            @endif
        </div>
        <div class="needle-sheet-titlebar">
            <div class="title">Daily Broken Needle Report</div>
            <div class="date">Date: {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</div>
        </div>

        <div class="table-responsive">
            <table class="table needle-form-table table-sm mb-0">
                <thead>
                    <tr>
                        <th class="needle-narrow">S.L</th>
                        <th>Line<br>No</th>
                        <th>Type Of<br>Needle</th>
                        <th>Needle<br>Size</th>
                        <th>Machine<br>No</th>
                        <th class="needle-tape-col">Attached Complete Broken<br>Needle With Tape Here</th>
                        <th>Name Of<br>Operator</th>
                        <th>Operator<br>ID No</th>
                        <th>Operator<br>Signature</th>
                        <th class="needle-narrow">1st</th>
                        <th class="needle-narrow">2nd</th>
                        <th class="needle-narrow">3rd</th>
                        <th class="needle-narrow">4th</th>
                        <th class="needle-narrow">5th</th>
                        <th>Remark</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td class="text-center">{{ $row->line_no }}</td>
                            <td>{{ $row->needle_type }}</td>
                            <td class="text-center">{{ $row->needle_size }}</td>
                            <td>{{ $row->machine?->name ?? $row->machine?->code ?? '' }}</td>
                            <td></td>
                            <td>{{ $row->employee?->name ?? '' }}</td>
                            <td class="text-center">{{ $row->employee?->employee_id ?? $row->employee_id }}</td>
                            <td></td>
                            @for($occ = 1; $occ <= 5; $occ++)
                                <td class="text-center">{{ $occ <= $row->occurrences ? 'X' : '' }}</td>
                            @endfor
                            <td>{{ $row->remarks }}</td>
                        </tr>
                    @empty
                    @endforelse
                    @for($i = $rows->count(); $i < max(18, $rows->count()); $i++)
                        <tr>
                            <td class="text-center">{{ $i + 1 }}</td>
                            <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>

        <div class="needle-instructions">
            <strong>INSTRUCTION:</strong>
            <ul class="mb-0 ps-3">
                <li>THE OPERATOR MUST STOP WORK IMMEDIATELY, COLLECT ALL BROKEN METAL/NEEDLE PIECES AND MUST BE SUBMITTED TO THE NEEDLE MAN TO RECEIVE A REPLACEMENT NEEDLE.</li>
                <li>THE NEEDLE MAN MUST TAPE THE BROKEN METAL/NEEDLE PIECES SECURELY TO A CONTROL CARD AND LOG IN REGISTER.</li>
                <li>IF ANY BROKEN PART IS MISSING, THE NEEDLE MAN WILL NOT ISSUE A NEW NEEDLE UNTIL AUTHORIZATION IS RECEIVED FROM PM.</li>
            </ul>
        </div>
    </div>
</div>
@unless($printMode)
    @include('sfl-inventory::admin.partials.select2-init')
@endunless
@endsection
