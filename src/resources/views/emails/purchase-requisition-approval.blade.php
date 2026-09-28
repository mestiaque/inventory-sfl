@php
    $pr = $purchaseRequisition;
    $td = 'border:1px solid #d5d9e0;padding:7px 9px;font-size:13px;color:#17233c;';
    $th = 'border:1px solid #d5d9e0;padding:7px 9px;font-size:12px;color:#17233c;background:#f3f5f8;text-align:left;';
    $label = 'color:#7b8794;font-size:12px;padding:3px 0;width:140px;vertical-align:top;';
    $value = 'color:#17233c;font-size:13px;padding:3px 0;font-weight:600;vertical-align:top;';
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Purchase Requisition - {{ $pr->requisition_no }}</title>
</head>
<body style="margin:0;padding:0;background:#eef1f5;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#eef1f5;padding:24px 12px;">
    <tr>
        <td align="center">
            <table width="100%" cellpadding="0" cellspacing="0" style="max-width:680px;background:#ffffff;border:1px solid #d5d9e0;border-radius:6px;">
                {{-- Company header --}}
                <tr>
                    <td style="padding:22px 24px 14px;border-bottom:2px solid #17233c;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="vertical-align:top;">
                                    <div style="font-size:20px;font-weight:700;color:#17233c;">{{ config('sfl-inventory.company.name') }}</div>
                                    <div style="font-size:12px;color:#555;margin-top:2px;">{{ config('sfl-inventory.company.address') }}</div>
                                </td>
                                <td style="vertical-align:top;text-align:right;white-space:nowrap;">
                                    <div style="display:inline-block;background:#17233c;color:#ffffff;font-size:12px;font-weight:700;letter-spacing:1px;padding:5px 12px;">PURCHASE REQUISITION</div>
                                    <div style="font-size:12px;color:#555;margin-top:8px;">No: <strong style="color:#17233c;">{{ $pr->requisition_no }}</strong></div>
                                    <div style="font-size:12px;color:#555;">Date: <strong style="color:#17233c;">{{ $pr->requisition_date?->format('d.m.Y') }}</strong></div>
                                    <div style="font-size:12px;color:#b7791f;font-weight:700;margin-top:4px;">PENDING APPROVAL</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Meta --}}
                <tr>
                    <td style="padding:16px 24px 6px;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr><td style="{{ $label }}">Requested by</td><td style="{{ $value }}">{{ $pr->requester?->name ?? '-' }}</td></tr>
                            <tr><td style="{{ $label }}">Department</td><td style="{{ $value }}">{{ $pr->department?->name ?? '-' }}</td></tr>
                            <tr><td style="{{ $label }}">Items</td><td style="{{ $value }}">{{ $pr->items->count() }}</td></tr>
                        </table>
                    </td>
                </tr>

                {{-- Items --}}
                <tr>
                    <td style="padding:10px 24px;">
                        <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                            <thead>
                                <tr>
                                    <th style="{{ $th }}width:30px;">#</th>
                                    <th style="{{ $th }}">Item</th>
                                    <th style="{{ $th }}text-align:right;">Qty</th>
                                    <th style="{{ $th }}">UOM</th>
                                    <th style="{{ $th }}text-align:right;">Est. Rate</th>
                                    <th style="{{ $th }}text-align:right;">Est. Amount (Tk)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pr->items as $line)
                                    <tr>
                                        <td style="{{ $td }}">{{ $loop->iteration }}</td>
                                        <td style="{{ $td }}">
                                            {{ $line->item?->item_name ?? '-' }}
                                            <div style="font-size:11px;color:#7b8794;">
                                                {{ $line->item?->item_code }}{{ $line->color ? ' · ' . $line->color->name : '' }}{{ $line->size ? ' · ' . $line->size->name : '' }}
                                            </div>
                                        </td>
                                        <td style="{{ $td }}text-align:right;">{{ inv_qty($line->requested_qty) }}</td>
                                        <td style="{{ $td }}">{{ $line->item?->unit?->short_name ?: '-' }}</td>
                                        <td style="{{ $td }}text-align:right;">{{ number_format((float) $line->estimated_rate, 2) }}</td>
                                        <td style="{{ $td }}text-align:right;">{{ number_format($line->estimated_amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="5" style="{{ $th }}text-align:right;font-size:13px;">Estimated Total</th>
                                    <th style="{{ $th }}text-align:right;font-size:14px;">{{ number_format($estimatedTotal, 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                        @if($amountInWords)
                            <div style="font-size:12px;color:#555;margin-top:8px;">
                                <strong style="color:#17233c;">Taka in word:</strong> <em>{{ $amountInWords }}</em>
                            </div>
                        @endif
                        <div style="font-size:11px;color:#7b8794;margin-top:6px;">
                            Estimated / market price given by the requester. The actual price is entered when the goods are received (GRN).
                        </div>
                    </td>
                </tr>

                @if($pr->remarks)
                    <tr>
                        <td style="padding:4px 24px 8px;font-size:12px;color:#555;">
                            <strong style="color:#17233c;">Justification:</strong> {{ $pr->remarks }}
                        </td>
                    </tr>
                @endif

                {{-- Footer / action --}}
                <tr>
                    <td style="padding:14px 24px 22px;">
                        <table width="100%" cellpadding="0" cellspacing="0" style="border-top:1px dashed #c3c9d2;">
                            <tr>
                                <td style="padding-top:12px;font-size:12px;color:#555;vertical-align:top;">
                                    Submitted by: <strong style="color:#17233c;">{{ $pr->requester?->name ?? 'N/A' }}</strong><br>
                                    Submitted at: {{ $pr->created_at?->format('d.m.Y h:i A') }}
                                </td>
                                <td style="padding-top:12px;text-align:right;vertical-align:top;">
                                    <a href="{{ $approvalUrl }}" style="display:inline-block;background:#1769e0;color:#ffffff;text-decoration:none;padding:10px 18px;border-radius:5px;font-size:13px;font-weight:700;">Review &amp; Approve</a>
                                </td>
                            </tr>
                        </table>
                        <p style="margin:16px 0 0;font-size:11px;color:#a0aab8;">
                            Sign in with an account that has Purchase Requisition approval permission to review this request.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
