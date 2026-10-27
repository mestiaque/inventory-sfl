@extends('printMaster2')

@section('title', 'Store Return Slip - ' . $return->return_no)

@push('css')
<style>
    .challan-title { text-align: center; margin-bottom: 15px; }
    .challan-title h2 { font-family: 'Times New Roman', Times, serif; font-weight: bold; margin-bottom: 2px; }
    .challan-title .sub { font-size: 12px; margin-bottom: 8px; }
    .challan-title .form-title { font-size: 15px; font-weight: bold; text-decoration: underline; }
    .info-grid { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
    .info-grid td { border: 1px solid #333; padding: 6px 10px; font-size: 12px; width: 50%; }
    .info-grid td b { display: inline-block; min-width: 130px; }
    .challan-table th, .challan-table td { text-align: center; font-size: 12px; }
    .challan-table td.text-start { text-align: left; }
    .total-row td { font-weight: bold; background: #e7e7e7; }
    .sign-grid-3 { display: flex; justify-content: space-between; margin-top: 40px; text-align: center; }
    .sign-grid-3 .box { width: 30%; font-size: 11px; }
    .sign-grid-3 .box .sig-slot { height: 40px; display: flex; align-items: flex-end; justify-content: center; }
    .sign-grid-3 .box .name { margin-top: 4px; border-top: 1px solid #333; padding-top: 4px; }
    .sign-grid-3 .box .role { font-weight: bold; margin-top: 2px; }
    .sign-grid-3 .box .signature-img { max-height: 38px; max-width: 100%; }
</style>
@endpush

@section('contents')
<div class="challan-title">
    @if(report_logo_url())<img src="{{ report_logo_url() }}" alt="Logo" style="display:block;height:50px;max-width:160px;object-fit:contain;margin:0 auto 4px;">@endif
    <h2>{{ strtoupper(config('sfl-inventory.company.name')) }}</h2>
    <div class="sub">{{ strtoupper(config('sfl-inventory.company.address') ?: 'KATHGORA, ASHULIA, SAVAR, DHAKA') }}</div>
    <div class="form-title">STORE RETURN SLIP</div>
</div>

<table class="info-grid">
    <tr>
        <td><b>Returned By Department:</b> {{ $return->issue?->department?->name }}</td>
        <td><b>Returned To Store:</b> {{ $return->store?->name }}</td>
    </tr>
    <tr>
        <td><b>Date of Return:</b> {{ $return->return_date?->format('d/m/Y') }}</td>
        <td><b>Return No:</b> {{ $return->return_no }}</td>
    </tr>
    <tr>
        <td><b>Against Issue Challan:</b> {{ $return->issue?->issue_no }}</td>
        <td><b>Issue Date:</b> {{ $return->issue?->issue_date?->format('d/m/Y') }}</td>
    </tr>
    <tr>
        <td><b>PO/Style/Order Ref:</b> {{ $return->issue?->style ?? $return->issue?->order_ref ?? '—' }}</td>
        <td><b>Buyer:</b> {{ $return->issue?->buyer?->name ?? '—' }}</td>
    </tr>
    <tr>
        <td colspan="2"><b>Reason:</b> {{ $return->reason ?? '—' }}</td>
    </tr>
</table>

<table class="challan-table">
    <thead>
        <tr>
            <th style="width:35px;">SL</th>
            <th>Item Code</th>
            <th>Item Name</th>
            <th>Category</th>
            <th>Color</th>
            <th>Size</th>
            <th>UOM</th>
            <th>Returned Qty</th>
        </tr>
    </thead>
    <tbody>
        @foreach($return->items as $line)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $line->item?->item_code }}</td>
                <td class="text-start">{{ $line->item?->item_name }}</td>
                <td>{{ $line->item?->category?->name }}</td>
                <td>{{ $line->color?->name ?? '---' }}</td>
                <td>{{ $line->size?->name ?? '---' }}</td>
                <td>{{ $line->item?->unit?->short_name }}</td>
                <td>{{ inv_qty($line->returned_qty) }}</td>
            </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="7" class="text-start">Total :</td>
            <td>{{ inv_qty($return->items->sum('returned_qty')) }}</td>
        </tr>
    </tbody>
</table>

<div class="sign-grid-3">
    <div class="box">
        <div class="role">Returned By (Department)</div>
        <div class="sig-slot"></div>
        <div class="name">&nbsp;</div>
    </div>
    <div class="box">
        <div class="role">Received By (Store)</div>
        <div class="sig-slot">
            @if($return->returner?->signature)
                <img class="signature-img" src="{{ asset($return->returner->signature) }}" alt="Signature">
            @endif
        </div>
        <div class="name">
            {{ $return->returner?->name }}<br>
            {{ $return->created_at?->format('d/m/Y h:i A') }}
        </div>
    </div>
    <div class="box">
        <div class="role">Store In-Charge</div>
        <div class="sig-slot"></div>
        <div class="name">&nbsp;</div>
    </div>
</div>
@endsection
