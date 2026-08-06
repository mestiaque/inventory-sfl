{{-- props: title (report title shown under the company block) --}}
<div class="print-header">
    <div class="company-info" style="justify-content:center; flex-direction:column; align-items:center; text-align:center;">
        <div class="company-name">{{ config('sfl-inventory.company.name') }}</div>
        @if(config('sfl-inventory.company.address'))
            <div class="company-address">{{ config('sfl-inventory.company.address') }}</div>
        @endif
    </div>
    <div class="report-title"><span>{{ $title }}</span></div>
</div>
