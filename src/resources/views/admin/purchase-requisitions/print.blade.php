@extends('printMaster2')

@section('title', 'Purchase Requisition - ' . $purchaseRequisition->requisition_no)

@push('css')
<style>
    .req-title { text-align: center; }
    .req-title h2 { font-family: 'Times New Roman', Times, serif; font-weight: bold; letter-spacing: 1px; margin-bottom: 2px; }
    .req-title .sub { font-size: 12px; letter-spacing: 1px; margin-bottom: 10px; }
    .req-title .form-title { font-size: 15px; font-weight: bold; text-decoration: underline; margin-bottom: 15px; }
    .req-meta-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px; }
    .req-meta-row span.field-line { display: inline-block; min-width: 160px; border-bottom: 1px solid #333; padding-bottom: 2px; }
    .req-table { width: 100%; }
    .req-table th, .req-table td { text-align: center; font-size: 12px; }
    .req-table td.text-start { text-align: left; }
    .req-table tfoot td { font-weight: bold; }
    .justification-line { margin-top: 20px; font-size: 13px; }
    .justification-line .fill { display: inline-block; border-bottom: 1px solid #333; min-width: 500px; }
    .sign-grid { display: flex; justify-content: space-between; margin-top: 60px; }
    .sign-grid .signature-box { width: 30%; text-align: center; }
    .sign-grid .sig-slot { height: 50px; display: flex; align-items: flex-end; justify-content: center; }
    .sign-grid .signature-img { max-height: 48px; max-width: 100%; }
    .sign-grid .signature-line { margin-top: 4px !important; }
    .note-line { margin-top: 20px; font-size: 12px; font-style: italic; font-weight: bold; }
</style>
@endpush

@php
    $pr = $purchaseRequisition;
    $isApproved = in_array($pr->status, ['approved', 'partially_converted', 'converted'], true);
@endphp

@section('contents')
<div class="req-title">
    <h2>{{ strtoupper(config('sfl-inventory.company.name')) }}</h2>
    <div class="sub">{{ strtoupper(config('sfl-inventory.company.address') ?: 'KATHGORA, ASHULIA, SAVAR, DHAKA') }}</div>
    <div class="form-title">PURCHASE REQUISITION</div>
</div>

<div class="req-meta-row">
    <div>Requisition No: <span class="field-line">{{ $pr->requisition_no }}</span></div>
    <div>Date: <span class="field-line">{{ $pr->requisition_date?->format('d-m-Y') }}</span></div>
</div>
<div class="req-meta-row">
    <div>Requested By: <span class="field-line">{{ $pr->requester?->name }}</span></div>
    <div>Department: <span class="field-line">{{ $pr->department?->name }}</span></div>
</div>
<div class="req-meta-row">
    <div>Status: <span class="field-line">{{ ucwords(str_replace('_', ' ', $pr->status)) }}</span></div>
    <div>Store Order(s): <span class="field-line">{{ $pr->purchaseOrders->pluck('po_number')->implode(', ') ?: '—' }}</span></div>
</div>

<table class="req-table">
    <thead>
        <tr>
            <th style="width:40px;">SL #</th>
            <th>Item Code</th>
            <th>Item</th>
            <th>Color</th>
            <th>Size</th>
            <th>Unit</th>
            <th>Qty Req.</th>
            <th>Est. Rate</th>
            <th>Est. Amount</th>
            <th>Qty Approved</th>
            <th>Qty Ordered</th>
            <th>Remarks</th>
        </tr>
    </thead>
    <tbody>
        @foreach($pr->items as $line)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $line->item?->item_code }}</td>
                <td class="text-start">{{ $line->item?->item_name }}</td>
                <td>{{ $line->color?->name }}</td>
                <td>{{ $line->size?->name }}</td>
                <td>{{ $line->item?->unit?->short_name }}</td>
                <td>{{ inv_qty($line->requested_qty) }}</td>
                <td>{{ $line->estimated_rate !== null ? number_format((float) $line->estimated_rate, 2) : '' }}</td>
                <td>{{ $line->estimated_rate !== null ? number_format($line->estimated_amount, 2) : '' }}</td>
                <td>{{ $line->approved_qty !== null ? inv_qty($line->approved_qty) : '' }}</td>
                <td>{{ inv_qty($line->converted_qty) }}</td>
                <td></td>
            </tr>
        @endforeach
        @for($i = $pr->items->count(); $i < 10; $i++)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
            </tr>
        @endfor
    </tbody>
    <tfoot>
        <tr>
            <td colspan="6" style="text-align:right;">Total</td>
            <td>{{ inv_qty($pr->items->sum('requested_qty')) }}</td>
            <td></td>
            <td>{{ number_format($pr->estimated_total, 2) }}</td>
            <td>{{ inv_qty($pr->items->sum('approved_qty')) }}</td>
            <td>{{ inv_qty($pr->items->sum('converted_qty')) }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>

<div class="justification-line">
    Justification :<span class="fill">{{ $pr->remarks }}</span>
</div>
@if($pr->approval_remarks)
    <div class="justification-line">
        Approval Remarks :<span class="fill">{{ $pr->approval_remarks }}</span>
    </div>
@endif

<div class="sign-grid">
    <div class="signature-box">
        <div class="sig-slot">
            @if($pr->requester?->signature)
                <img class="signature-img" src="{{ asset($pr->requester->signature) }}" alt="Signature">
            @endif
        </div>
        <div class="signature-line">Requested By{{ $pr->requester?->name ? ' — ' . $pr->requester->name : '' }}</div>
    </div>
    <div class="signature-box">
        <div class="sig-slot"></div>
        <div class="signature-line">Checked By</div>
    </div>
    <div class="signature-box">
        <div class="sig-slot">
            @if($isApproved && $pr->approver?->signature)
                <img class="signature-img" src="{{ asset($pr->approver->signature) }}" alt="Signature">
            @endif
        </div>
        <div class="signature-line">Approved By{{ $isApproved && $pr->approver ? ' — ' . $pr->approver->name : '' }}</div>
    </div>
</div>

<div class="note-line">Note : Incomplete form will not be authorized</div>
@endsection
