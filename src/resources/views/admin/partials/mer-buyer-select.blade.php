{{--
    Buyer picker (Merchandising buyers — Inventory keeps no buyer list of its
    own). Posts msfl_buyer_id; the request maps it to the inventory buyer_id
    (Concerns\PicksMerchandisingStyle::mapPickedBuyer). An older record whose
    inventory buyer has no Merchandising match keeps it as "inv-buyer:<id>".

    props: currentBuyerId (the record's inventory buyer_id, optional),
           label (default 'Buyer'), required (bool), disabled (bool), help (text)
    + merBuyersOptions (MerchandisingLink::formOptions()).
--}}
@php
    $mbLink = app(\ME\SflInventory\Services\MerchandisingLink::class);
    $mbCurrent = $currentBuyerId ?? null;
    $mbCurrentMer = $mbLink->merBuyerIdFor($mbCurrent);
    $mbKeep = $mbCurrent && ! $mbCurrentMer ? \ME\SflInventory\Models\InvBuyer::withTrashed()->find($mbCurrent) : null;
    $mbSelected = (string) old('msfl_buyer_id', $mbCurrentMer ?? ($mbKeep ? \ME\SflInventory\Services\MerchandisingLink::LEGACY_BUYER . $mbKeep->id : ''));
@endphp
<label class="form-label">{{ $label ?? 'Buyer' }} @if($required ?? false)<span class="text-danger">*</span>@endif</label>
<select name="msfl_buyer_id" class="form-control form-control-sm inv-select2" @required($required ?? false) @disabled($disabled ?? false)>
    <option value="">— None —</option>
    @foreach($merBuyersOptions as $b)
        <option value="{{ $b->id }}" @selected($mbSelected === (string) $b->id)>{{ $b->name }}</option>
    @endforeach
    @if($mbKeep)
        <option value="{{ \ME\SflInventory\Services\MerchandisingLink::LEGACY_BUYER . $mbKeep->id }}" @selected($mbSelected === \ME\SflInventory\Services\MerchandisingLink::LEGACY_BUYER . $mbKeep->id)>{{ $mbKeep->name }} (Inventory)</option>
    @endif
</select>
@if(! empty($help))
    <div class="form-text">{{ $help }}</div>
@endif
