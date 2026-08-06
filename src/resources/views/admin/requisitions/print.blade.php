@extends('printMaster2')

@section('title', 'Requisition Form - ' . $requisition->requisition_no)

@push('css')
<style>
    .req-title { text-align: center; }
    .req-title h2 { font-family: 'Times New Roman', Times, serif; font-weight: bold; letter-spacing: 1px; margin-bottom: 2px; }
    .req-title .sub { font-size: 12px; letter-spacing: 1px; margin-bottom: 10px; }
    .req-title .form-title { font-size: 15px; font-weight: bold; text-decoration: underline; margin-bottom: 15px; }
    .req-meta-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px; }
    .req-meta-row span.field-line { display: inline-block; min-width: 160px; border-bottom: 1px solid #333; padding-bottom: 2px; }
    .req-checkbox-row { font-size: 13px; margin-bottom: 8px; }
    .req-checkbox-row .box { display: inline-block; width: 12px; height: 12px; border: 1px solid #333; text-align: center; line-height: 11px; font-weight: bold; margin: 0 4px 0 2px; vertical-align: middle; }
    .req-table th, .req-table td { text-align: center; font-size: 12px; }
    .req-table td.text-start { text-align: left; }
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

@section('contents')
<div class="req-title">
    <h2>{{ strtoupper(config('sfl-inventory.company.name')) }}</h2>
    <div class="sub">{{ strtoupper(config('sfl-inventory.company.address') ?: 'KATHGORA, ASHULIA, SAVAR, DHAKA') }}</div>
    <div class="form-title">REQUISITION FORM</div>
</div>

<div class="req-meta-row">
    <div>Name: <span class="field-line">{{ $requisition->requester?->name }}</span></div>
    <div>Designation: <span class="field-line">&nbsp;</span></div>
    <div>Date: <span class="field-line">{{ $requisition->requisition_date?->format('d-m-Y') }}</span></div>
</div>

<div class="req-checkbox-row">
    Department:
    @foreach($departments as $dept)
        {{ $dept->name }}<span class="box">{{ $requisition->department_id === $dept->id ? '✓' : '' }}</span>
    @endforeach
</div>

<div class="req-checkbox-row text-end" style="text-align:right;">
    Requisition For:
    @foreach(['Fabrics' => 'fabrics', 'Accessories' => 'accessories', 'MachineParts' => 'machine_parts', 'Equipment' => 'equipment', 'Stationery' => 'stationery'] as $label => $value)
        {{ $label }}<span class="box">{{ $requisition->requisition_for === $value ? '✓' : '' }}</span>
    @endforeach
</div>

<div class="req-meta-row"><div>Buyer: <span class="field-line">{{ $requisition->buyer?->name }}</span></div></div>

<table class="req-table">
    <thead>
        <tr>
            <th style="width:40px;">SL #</th>
            <th>Item</th>
            <th>Po/Style No./<br>Model No.</th>
            <th>Color</th>
            <th>Size</th>
            <th>Qty Req.</th>
            <th>Qty Issued</th>
            <th>Remarks</th>
        </tr>
    </thead>
    <tbody>
        @foreach($requisition->items as $line)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td class="text-start">{{ $line->item?->item_name }}</td>
                <td>{{ $requisition->style ?? $requisition->order_ref }}</td>
                <td>{{ $line->item?->color?->name }}</td>
                <td>{{ $line->item?->size?->name }}</td>
                <td>{{ inv_qty($line->requested_qty) }}</td>
                <td>{{ inv_qty($line->issued_qty) }}</td>
                <td>{{ $line->remarks }}</td>
            </tr>
        @endforeach
        @for($i = $requisition->items->count(); $i < 10; $i++)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td>
            </tr>
        @endfor
    </tbody>
</table>

<div class="justification-line">
    Justification :<span class="fill">{{ $requisition->remarks }}</span>
</div>

<div class="sign-grid">
    <div class="signature-box">
        <div class="sig-slot">
            @if($requisition->requester?->signature)
                <img class="signature-img" src="{{ asset($requisition->requester->signature) }}" alt="Signature">
            @endif
        </div>
        <div class="signature-line">Issued by{{ $requisition->requester?->name ? ' — ' . $requisition->requester->name : '' }}</div>
    </div>
    <div class="signature-box">
        <div class="sig-slot"></div>
        <div class="signature-line">Received by{{ $requisition->receiver?->name ? ' — ' . $requisition->receiver->name : '' }}</div>
    </div>
    <div class="signature-box">
        <div class="sig-slot">
            @if($requisition->status === 'approved' && $requisition->approver?->signature)
                <img class="signature-img" src="{{ asset($requisition->approver->signature) }}" alt="Signature">
            @endif
        </div>
        <div class="signature-line">Authorized By{{ $requisition->status === 'approved' && $requisition->approver ? ' — ' . $requisition->approver->name : '' }}</div>
    </div>
</div>

<div class="note-line">Note : Incomplete form will not be authorized</div>
@endsection
