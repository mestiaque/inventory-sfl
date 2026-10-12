@extends('printMaster2')

@section('title', 'Delivery Challan — ' . $challan->challan_no)

@push('css')
<style>
    @page { size: A4 portrait; margin: 10mm; }
    .sc-frame { border: 2px solid #1a3c8c; padding: 14px 18px; }
    .sc-head { text-align: center; margin-bottom: 8px; }
    .sc-head .sc-name { font-size: 20px; font-weight: 800; letter-spacing: .5px; }
    .sc-head .sc-addr { font-size: 11px; font-weight: 600; }
    .sc-title { text-align: center; font-size: 15px; font-weight: 700; text-decoration: underline; margin: 8px 0 10px; }
    .sc-meta-row { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 8px; }
    .sc-to { font-size: 12px; margin-bottom: 10px; }
    .sc-to .row { display: flex; margin-bottom: 2px; }
    .sc-to .lbl { width: 85px; flex-shrink: 0; }
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
        <div><strong>Challan No.</strong> : {{ $challan->challan_no }}</div>
        <div><strong>Date:</strong> {{ $challan->challan_date->format('d.m.y') }}</div>
    </div>

    <div class="sc-to">
        <div class="row"><div class="lbl">To</div><div>: {{ $challan->to_name }}</div></div>
        <div class="row"><div class="lbl">Address</div><div>: {{ $challan->to_address }}</div></div>
        <div class="row"><div class="lbl">Contact No.</div><div>: {{ $challan->to_contact }}</div></div>
    </div>

    <table class="sc-table">
        <thead>
            <tr>
                <th style="width:36px">SL NO</th>
                <th>ITEM</th>
                <th>PO</th>
                <th>STYLE</th>
                <th>COLOUR</th>
                <th>SAMPLE<br>OF BUYER</th>
                <th>P.P SAMPLE<br>FROM SFL</th>
                <th>SIZE</th>
                <th>TOTAL</th>
                <th>UNIT</th>
                <th>REMARKS</th>
            </tr>
        </thead>
        <tbody>
            @foreach($challan->items as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="text-left">{{ $item->item_name }}</td>
                    <td>{{ $item->po_no }}</td>
                    <td>{{ $item->style_no }}</td>
                    <td>{{ $item->color_name }}</td>
                    <td>{{ $item->buyer_sample_qty ?: '' }}</td>
                    <td>{{ $item->pp_sample_qty ?: '' }}</td>
                    <td>{{ $item->size->name ?? '' }}</td>
                    <td>{{ $item->total_qty ?: '' }}</td>
                    <td>{{ $item->unit }}</td>
                    <td class="text-left">{{ $item->remarks }}</td>
                </tr>
            @endforeach
            @for($i = $challan->items->count(); $i < 10; $i++)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                </tr>
            @endfor
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-left">Total</td>
                <td>{{ $challan->items->sum('buyer_sample_qty') ?: '' }}</td>
                <td>{{ $challan->items->sum('pp_sample_qty') ?: '' }}</td>
                <td></td>
                <td>{{ $challan->items->sum('total_qty') ?: '' }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>

    <div class="sc-note">Received the above goods in good condition</div>

    <div class="sc-for">For - {{ config('sfl-inventory.company.name') }}</div>

    <div class="sc-sign-grid">
        <div class="box">Receiver's Signature</div>
        <div class="box">Store Incharge{{ $challan->issuer?->name ? ' — ' . $challan->issuer->name : '' }}</div>
        <div class="box">Authorised Signature</div>
    </div>
</div>
@endsection
