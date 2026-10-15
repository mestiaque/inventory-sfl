@extends('printMaster2')

@section('title', 'Delivery Challan — ' . $gate_pass->gate_pass_no)

@push('css')
<style>
    @page { size: A4 landscape; margin: 10mm; }
    .sc-frame { border: 2px solid #1a3c8c; padding: 14px 18px; }
    .sc-head { text-align: center; margin-bottom: 8px; }
    .sc-head .sc-name { font-size: 20px; font-weight: 800; letter-spacing: .5px; }
    .sc-head .sc-addr { font-size: 11px; font-weight: 600; }
    .sc-title { text-align: center; font-size: 15px; font-weight: 700; text-decoration: underline; margin: 8px 0 10px; }
    .sc-meta-row { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 8px; }
    .sc-to { font-size: 12px; margin-bottom: 10px; display: flex; justify-content: space-between; flex-wrap: wrap; }
    .sc-to .col { width: 48%; }
    .sc-to .row { display: flex; margin-bottom: 2px; }
    .sc-to .lbl { width: 110px; flex-shrink: 0; }
    .sc-table { width: 100%; border-collapse: collapse; }
    .sc-table th, .sc-table td { border: 1px solid #333; padding: 4px 6px; font-size: 11px; text-align: center; }
    .sc-table thead th { background: #e5e7eb; font-weight: 700; }
    .sc-table td.text-left { text-align: left; }
    .sc-table tfoot td { font-weight: 700; }
    .sc-note { margin-top: 10px; font-size: 12px; }
    .sc-for { text-align: right; font-size: 12px; margin-top: 30px; font-weight: 600; }
    .sc-sign-grid { display: flex; justify-content: space-between; margin-top: 50px; font-size: 12px; }
    .sc-sign-grid .box { width: 30%; text-align: center; border-top: 1px solid #333; padding-top: 4px; font-weight: 600; }
</style>
@endpush

@section('contents')
<div class="sc-frame">
    <div class="sc-head">
        <div class="sc-name">{{ strtoupper(config('sfl-inventory.company.name')) }}</div>
        <div class="sc-addr">{{ strtoupper(config('sfl-inventory.company.address')) }}</div>
    </div>

    <div class="sc-title">Delivery Challan</div>

    <div class="sc-meta-row">
        <div><strong>Challan No.</strong> : {{ $gate_pass->gate_pass_no }}</div>
        <div><strong>Date:</strong> {{ $gate_pass->gate_pass_date->format('d.m.y') }}</div>
    </div>

    <div class="sc-to">
        <div class="col">
            <div class="row"><div class="lbl">To</div><div>: {{ $gate_pass->buyer?->name }}</div></div>
            <div class="row"><div class="lbl">Address</div><div>: {{ $gate_pass->buyer?->address }}</div></div>
            <div class="row"><div class="lbl">Contact No.</div><div>: {{ $gate_pass->buyer?->contact ?: '—' }}</div></div>
        </div>
        <div class="col">
            <div class="row"><div class="lbl">Vehicle No.</div><div>: {{ $gate_pass->vehicle_no ?: '—' }}</div></div>
            <div class="row"><div class="lbl">Driver</div><div>: {{ collect([$gate_pass->driver_name, $gate_pass->driver_contact])->filter()->implode(' / ') ?: '—' }}</div></div>
            <div class="row"><div class="lbl">Invoice No.</div><div>: {{ $gate_pass->shipment?->invoice_no ?: '—' }}</div></div>
            <div class="row"><div class="lbl">Packing List No.</div><div>: {{ $gate_pass->shipment?->packing_list_no ?: '—' }}</div></div>
        </div>
    </div>

    <table class="sc-table">
        <thead>
            <tr>
                <th style="width:32px">SL<br>NO.</th>
                <th>DESCRIPTION<br>OF ITEM</th>
                <th>PO<br>NUMBER</th>
                <th>STYLE<br>NO.</th>
                <th>COLOR</th>
                @foreach($sizes as $size)<th>{{ $size->name }}</th>@endforeach
                <th>TOTAL<br>QTY.</th>
                <th>UNIT</th>
                <th>CTN</th>
                <th>REMARKS</th>
            </tr>
        </thead>
        <tbody>
            @php $grand = array_fill_keys($sizes->pluck('id')->all(), 0); @endphp
            @foreach($gate_pass->items as $line)
                @php
                    $po = $posByItemId[$line->id] ?? null;
                    $figures = $figuresByItemId[$line->id] ?? null;
                    $lineTotal = 0;
                @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="text-left">{{ $po?->style?->name ?: $line->item?->item_name }}</td>
                    <td>{{ $po?->po_no }}</td>
                    <td>{{ $po?->style?->style_no }}</td>
                    <td>{{ $po?->color?->name }}</td>
                    @foreach($sizes as $size)
                        @php
                            $q = (int) ($figures['packed'][$size->id] ?? 0);
                            $lineTotal += $q;
                            $grand[$size->id] = ($grand[$size->id] ?? 0) + $q;
                        @endphp
                        <td>{{ $q ?: '' }}</td>
                    @endforeach
                    @php $grandQty = ($grandQty ?? 0) + ($figures && $lineTotal ? $lineTotal : (float) $line->quantity); @endphp
                    <td>{{ $figures && $lineTotal ? $lineTotal : inv_qty($line->quantity) }}</td>
                    <td>{{ $line->item?->unit?->short_name }}</td>
                    <td>{{ $line->carton_qty ?: ($figures['cartons'] ?? '') }}</td>
                    <td class="text-left">{{ $line->remarks }}</td>
                </tr>
            @endforeach
            @for($i = $gate_pass->items->count(); $i < 8; $i++)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>&nbsp;</td><td></td><td></td><td></td>
                    @foreach($sizes as $size)<td></td>@endforeach
                    <td></td><td></td><td></td><td></td>
                </tr>
            @endfor
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-left">Total-</td>
                @foreach($sizes as $size)<td>{{ $grand[$size->id] ?: '' }}</td>@endforeach
                <td>{{ inv_qty($grandQty ?? 0) }}</td>
                <td></td>
                <td>{{ $gate_pass->items->sum('carton_qty') ?: ($figuresByItemId->sum(fn ($f) => $f['cartons'] ?? 0) ?: '') }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="sc-note">Received the above goods in good condition</div>

    <div class="sc-for">For - {{ config('sfl-inventory.company.name') }}</div>

    <div class="sc-sign-grid">
        <div class="box">Receiver's Signature</div>
        <div class="box">Authorised Signature</div>
    </div>
</div>
@endsection
