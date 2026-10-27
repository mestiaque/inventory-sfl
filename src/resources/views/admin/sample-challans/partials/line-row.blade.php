{{-- props: index, line (array), orderPoOptions ([id => ['label','buyer_id']]), sizes --}}
<tr data-sci-row>
    <td style="min-width:260px">
        <select name="items[{{ $index }}][msfl_order_po_id]" class="form-control form-control-sm inv-select2" data-sci-po required>
            <option value="">— PO / Style / Colour —</option>
            @foreach($orderPoOptions as $poId => $po)
                <option value="{{ $poId }}" data-buyer="{{ $po['buyer_id'] }}"
                    @selected((string) ($line['msfl_order_po_id'] ?? '') === (string) $poId)>{{ $po['label'] }}</option>
            @endforeach
        </select>
    </td>
    <td style="min-width:90px">
        <select name="items[{{ $index }}][size_id]" class="form-control form-control-sm">
            <option value="">—</option>
            @foreach($sizes as $size)
                <option value="{{ $size->id }}" @selected((string) ($line['size_id'] ?? '') === (string) $size->id)>{{ $size->name }}</option>
            @endforeach
        </select>
    </td>
    <td style="width:110px"><input type="number" step="1" min="0" name="items[{{ $index }}][buyer_sample_qty]" class="form-control form-control-sm text-right" value="{{ $line['buyer_sample_qty'] ?? '' }}"></td>
    <td style="width:110px"><input type="number" step="1" min="0" name="items[{{ $index }}][pp_sample_qty]" class="form-control form-control-sm text-right" value="{{ $line['pp_sample_qty'] ?? '' }}"></td>
    <td style="width:90px"><input type="text" name="items[{{ $index }}][unit]" class="form-control form-control-sm" value="{{ $line['unit'] ?? 'Pcs' }}"></td>
    <td style="min-width:160px"><input type="text" name="items[{{ $index }}][remarks]" class="form-control form-control-sm" value="{{ $line['remarks'] ?? '' }}" placeholder="e.g. P.P Sample"></td>
    <td style="width:40px"><button type="button" class="btn-custom ui-act ui-act-delete" data-line-items-remove title="Delete"><i class="fa-solid fa-trash"></i></button></td>
</tr>
