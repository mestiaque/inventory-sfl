@extends('printMaster2')

@section('title', 'Store Delivery Challan - ' . $issue->issue_no)

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
    .desc-grid { width: 100%; border-collapse: collapse; margin: 15px 0; }
    .desc-grid th, .desc-grid td { border: 1px solid #333; padding: 8px; font-size: 12px; text-align: center; width: 50%; }
    .status-badge p-1 text-white { display: inline-block; padding: 2px 10px; border-radius: 3px; font-size: 11px; font-weight: bold; color: #fff; }
    .sign-grid-3 { display: flex; justify-content: space-between; margin-top: 30px; text-align: center; }
    .sign-grid-3 .box { width: 24%; font-size: 11px; }
    .sign-grid-3 .box .sig-slot { height: 40px; display: flex; align-items: flex-end; justify-content: center; }
    .sign-grid-3 .box .name { margin-top: 4px; border-top: 1px solid #333; padding-top: 4px; }
    .sign-grid-3 .box .role { font-weight: bold; margin-top: 2px; }
    .sign-grid-3 .box .signature-img, .sign-grid-2 .box .signature-img { max-height: 38px; max-width: 100%; }
    .sign-grid-2 { display: flex; justify-content: space-between; margin-top: 25px; text-align: center; }
    .sign-grid-2 .box { width: 32%; font-size: 11px; }
    .sign-grid-2 .box .sig-slot { height: 40px; display: flex; align-items: flex-end; justify-content: center; }
    .sign-grid-2 .box .name { margin-top: 4px; border-top: 1px solid #333; padding-top: 4px; }
    .sign-grid-2 .box .role { font-weight: bold; margin-top: 2px; }
</style>
@endpush

@section('contents')
<div class="challan-title">
    <h2>{{ strtoupper(config('sfl-inventory.company.name')) }}</h2>
    <div class="sub">{{ strtoupper(config('sfl-inventory.company.address') ?: 'KATHGORA, ASHULIA, SAVAR, DHAKA') }}</div>
    <div class="form-title">STORE DELIVERY CHALLAN</div>
</div>

<table class="info-grid">
    <tr>
        <td><b>Issued From:</b> {{ $issue->store?->name }}</td>
        <td><b>Issued To Department:</b> {{ $issue->department?->name }}</td>
    </tr>
    <tr>
        <td><b>Date of Issue:</b> {{ $issue->issue_date?->format('d/m/Y') }}</td>
        <td><b>Time of Issue:</b> {{ $issue->created_at?->format('h:i A') }}</td>
    </tr>
    <tr>
        <td><b>PO/Style/Order Ref:</b> {{ $issue->style ?? $issue->order_ref ?? '—' }}</td>
        <td><b>Buyer:</b> {{ $issue->buyer?->name ?? '—' }}</td>
    </tr>
    <tr>
        <td><b>Ref No:</b> {{ $issue->requisition?->requisition_no ?? '—' }}</td>
        <td><b>Challan No:</b> {{ $issue->issue_no }}</td>
    </tr>
    <tr>
        <td colspan="2">
            <b>Status:</b>
            <span class="status-badge p-1 text-white" style="background:{{ ['pending' => '#6c757d', 'authorized' => '#0d6efd', 'approved' => '#198754'][$issue->status] ?? '#6c757d' }}">
                {{ ucfirst($issue->status) }}
            </span>
        </td>
    </tr>
</table>

<table class="challan-table">
    <thead>
        <tr>
            <th style="width:35px;">SL</th>
            <th>Item Code</th>
            <th>Item Name</th>
            <th>Category</th>
            <th>Description</th>
            <th>UOM</th>
            <th>Qty</th>
        </tr>
    </thead>
    <tbody>
        @foreach($issue->items as $line)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $line->item?->item_code }}</td>
                <td class="text-start">{{ $line->item?->item_name }}</td>
                <td>{{ $line->item?->category?->name }}</td>
                <td>{{ $line->item?->specification ?? '---' }}</td>
                <td>{{ $line->item?->unit?->short_name }}</td>
                <td>{{ inv_qty($line->issued_qty) }}</td>
            </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="6" class="text-start">Total :</td>
            <td>{{ inv_qty($issue->items->sum('issued_qty')) }}</td>
        </tr>
    </tbody>
</table>

<table class="desc-grid">
    <thead><tr><th>Description</th><th>Remarks</th></tr></thead>
    <tbody><tr><td>{{ $issue->style ?? '—' }}</td><td>{{ $issue->remarks ?? '—' }}</td></tr></tbody>
</table>

<div class="sign-grid-3">
    <div class="box">
        <div class="role">Prepared By</div>
        <div class="sig-slot">
            @if($issue->issuer?->signature)
                <img class="signature-img" src="{{ asset($issue->issuer->signature) }}" alt="Signature">
            @endif
        </div>
        <div class="name">
            {{ $issue->issuer?->name }}<br>
            {{ $issue->created_at?->format('d/m/Y h:i A') }}
        </div>
    </div>
    <div class="box">
        <div class="role">Authorized By</div>
        <div class="sig-slot">
            @if($issue->authorizer?->signature)
                <img class="signature-img" src="{{ asset($issue->authorizer->signature) }}" alt="Signature">
            @endif
        </div>
        <div class="name">
            {{ $issue->authorizer?->name }}<br>
            {{ $issue->authorized_at?->format('d/m/Y h:i A') }}
        </div>
    </div>
    <div class="box">
        <div class="role">Approved By</div>
        <div class="sig-slot">
            @if($issue->approver?->signature)
                <img class="signature-img" src="{{ asset($issue->approver->signature) }}" alt="Signature">
            @endif
        </div>
        <div class="name">
            {{ $issue->approver?->name }}<br>
            {{ $issue->approved_at?->format('d/m/Y h:i A') }}
        </div>
    </div>
    <div class="box">
        <div class="role">Warehouse Prepared By</div>
        <div class="sig-slot"></div>
        <div class="name">&nbsp;</div>
    </div>
</div>

<div class="sign-grid-2">
    <div class="box">
        <div class="role">Warehouse Authorized By</div>
        <div class="sig-slot"></div>
        <div class="name">&nbsp;</div>
    </div>
    <div class="box">
        <div class="role">Security Checked By</div>
        <div class="sig-slot"></div>
        <div class="name">&nbsp;</div>
    </div>
    <div class="box">
        <div class="role">Received Name, Number &amp; Remarks</div>
        <div class="sig-slot"></div>
        <div class="name">
            {{ $issue->requisition?->receiver?->name ?? $issue->departmentReceiver?->name }}<br>
            {{ $issue->requisition?->receiver?->mobile ?? '' }}<br>
            {{ $issue->department_received_at?->format('d/m/Y h:i A') }}
        </div>
    </div>
</div>
@endsection
