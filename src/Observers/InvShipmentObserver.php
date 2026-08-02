<?php

namespace ME\SflInventory\Observers;

use ME\SflInventory\Models\InvShipment;
use ME\SflInventory\Services\DocumentNumberService;

class InvShipmentObserver
{
    public function __construct(private readonly DocumentNumberService $documentNumbers)
    {
    }

    public function creating(InvShipment $shipment): void
    {
        if (empty($shipment->shipment_no)) {
            $shipment->shipment_no = $this->documentNumbers->next(
                InvShipment::class,
                'shipment_no',
                config('sfl-inventory.document_prefixes.shipment', 'SHP')
            );
        }
    }
}
