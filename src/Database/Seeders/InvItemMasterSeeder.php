<?php

namespace ME\SflInventory\Database\Seeders;

use Illuminate\Database\Seeder;
use ME\SflInventory\Models\InvDepartment;
use ME\SflInventory\Models\InvItem;
use ME\SflInventory\Models\InvItemCategory;
use ME\SflInventory\Models\InvUnit;

/**
 * Real factory item master data, section-wise (each section = both an
 * inv_item_categories row AND a matching inv_departments row, same code —
 * e.g. code 'CT' is the "Cutting Section" category items are grouped under
 * AND the "Cutting" department that requisitions them). item_code prefix
 * matches, e.g. SFL-SW-001. Re-runnable: uses updateOrCreate keyed on
 * item_code/code, so running it again just fills in newly added rows
 * without duplicating.
 */
class InvItemMasterSeeder extends Seeder
{
    public function run(): void
    {
        $unit = InvUnit::firstOrCreate(['short_name' => 'PCS'], ['name' => 'Piece']);

        foreach ($this->sections() as $code => [$name, $items]) {
            $category = InvItemCategory::firstOrCreate(['code' => $code], ['name' => $name]);
            $department = InvDepartment::firstOrCreate(['code' => $code], ['name' => $name, 'is_active' => true]);

            foreach ($items as $seq => $itemName) {
                $itemCode = sprintf('SFL-%s-%03d', $code, $seq);

                InvItem::updateOrCreate(
                    ['item_code' => $itemCode],
                    [
                        'item_name'     => $itemName,
                        'category_id'   => $category->id,
                        'department_id' => $department->id,
                        'unit_id'       => $unit->id,
                        'item_type'     => 'raw_material',
                        'is_active'     => true,
                    ]
                );
            }
        }
    }

    /**
     * @return array<string, array{0: string, 1: array<int, string>}>
     */
    private function sections(): array
    {
        return [
            'SW' => ['Sewing Section', [
                1 => 'UY-7', 2 => 'UY-9', 3 => 'UY-10', 4 => 'UY-11', 5 => 'UY-12', 6 => 'UY-14', 7 => 'UY-16',
                8 => 'UY-18', 9 => 'UY-20', 10 => 'UY-22', 11 => 'DC-7', 12 => 'DC-9', 13 => 'DC-10', 14 => 'DC-11',
                15 => 'DC-12', 16 => 'DC-14', 17 => 'DC-16', 18 => 'DC-18', 19 => 'DC-20', 20 => 'DB-7', 21 => 'DB-9',
                22 => 'DB-10', 23 => 'DB-11', 24 => 'DB-12', 25 => 'DB-14', 26 => 'DB-16', 27 => 'DB-18', 28 => 'DB-20',
                29 => 'DP5-7', 30 => 'DP5-9', 31 => 'DP5-10', 32 => 'DP5-11', 33 => 'DP5-12', 34 => 'DP5-14',
                35 => 'DP5-16', 36 => 'DP5-18', 37 => 'DP5-20', 38 => 'DP5-22', 39 => 'DP17-7', 40 => 'DP17-9',
                41 => 'DP17-11', 42 => 'DP17-14', 43 => 'DP17-16', 44 => 'DP17-18', 45 => 'DP17-20', 46 => 'DP17-22',
                47 => 'DO-14', 48 => 'DO-16', 49 => 'DO-18', 50 => 'DO-19', 51 => 'DO-20', 52 => 'TV(64)-11',
                53 => 'TV(64)-14', 54 => 'TV(64)-16', 55 => 'TV(64)-18', 56 => 'UO-11', 57 => 'UO-14', 58 => 'UO-16',
                59 => 'UO-18', 60 => 'UO-20', 61 => 'UO-22', 62 => 'Magic pen', 63 => 'Mom chalk',
                64 => 'Magnet gaide', 65 => 'Bobbin', 66 => 'Bobbin Case', 67 => 'Presser feed', 68 => 'Needle Plate',
                69 => 'Feed Dog', 70 => 'Sewing Machine Oil', 71 => 'Thread Stand', 72 => 'Bon kata Scissors',
                73 => 'Cutter', 74 => 'Overlock uapar loar Knife', 75 => 'Loopers', 76 => 'Belt (Machine)',
                77 => 'Motor', 78 => 'Machine Spare Parts', 79 => 'Plan feed', 80 => 'P/m auto feed dog',
                81 => 'Hangar Guide', 82 => 'Chain stitch 1/4 gaide CL/CR', 83 => '2 needle 1/4 gaide CL/CR',
                84 => 'Ovar look sqwro', 85 => 'P/m 1/16 CR/CL', 86 => 'P/m Zipper Guiide L/R',
                87 => 'P/m shiaring gaide', 88 => 'Stitch Openar', 89 => 'Magic thread',
            ]],
            'CT' => ['Cutting Section', [
                1 => 'Cutting Knife', 2 => 'Straight Knife Machine', 3 => 'Round Knife Machine',
                4 => 'Band Knife Blade', 5 => 'Pattern Board', 6 => 'Marker Paper', 7 => 'Fabric Spreading Machine',
                8 => 'Measurment Tape', 9 => 'Fabric Weight', 10 => 'Blitz Nambaring Machine', 11 => 'Drill Machine',
                12 => 'Fabric Marker', 13 => 'Color Chalk', 14 => 'Cutting Table', 15 => 'Auto Sticker',
                16 => 'Auto ink', 17 => 'Servicing Belt', 18 => 'Gum tape', 19 => 'Rollax pawdar',
            ]],
            'FN' => ['Finishing Section', [
                1 => 'Spot Cleaning Gun', 2 => 'Stain Remover', 3 => 'Camical', 4 => 'Fabric Brush',
                5 => 'Folding Board', 6 => 'Garment Hanger', 7 => 'Thread Sucker Machine', 8 => 'Quality Tag',
                9 => 'Inspection Sticker', 10 => 'Iron M/C', 11 => 'Iron Pipe', 12 => 'Iron show 300/300l',
                13 => 'Thinner', 14 => 'Lifter', 15 => 'Qc Pass', 16 => 'Waist code poly', 17 => 'Tag-gun m/c',
            ]],
            'PK' => ['Packaging', [
                1 => 'Poly Bag', 2 => 'Carton Box', 3 => 'Tissue Paper', 4 => 'Hanger', 5 => 'Barcode Sticker',
                6 => 'Packing Tape', 7 => 'Carton Strap', 8 => 'Back Board', 9 => 'Colar stand', 10 => 'Hand Tag',
                11 => 'Neck PS', 12 => 'M Clip', 13 => 'J Clip', 14 => 'String', 15 => 'Colar Bon',
                16 => 'Silica Gel', 17 => 'Poly Sticker', 18 => 'Cartoon Stricker',
            ]],
            'TR' => ['Trims', [
                1 => 'Sewing Thread', 2 => 'Cotton Thread', 3 => 'Embroidery Thread', 4 => 'Elastic (All Types)',
                5 => 'Twill Tape', 6 => 'Bias Tape', 7 => 'Fusible Interlining', 8 => 'Non-Fusible Interlining',
                9 => 'Velco tape', 10 => 'Drawcord', 11 => 'Lace', 12 => 'Ribbon',
            ]],
            'LB' => ['Labels & Branding Items', [
                1 => 'Main Label (Brand)', 2 => 'Size Label', 3 => 'Care Label', 4 => 'Woven Label',
                5 => 'Printed Label', 6 => 'Hang Tag', 7 => 'Price Tag', 8 => 'Leather Label',
                9 => 'Heat Transfer Label', 10 => 'Fit label',
            ]],
            'OF' => ['Office Supply', [
                1 => 'A4 Paper', 2 => 'File Box', 3 => 'Marker', 4 => 'Stapler m/c', 5 => 'Staple Pin',
                6 => 'Calculator', 7 => 'Printer', 8 => 'Printer Ink', 9 => 'Register Book', 10 => 'Clip Board',
                11 => 'Envelope', 12 => 'Tape Dispenser/ Holder', 13 => 'Scissors', 14 => 'Robban Tin',
                15 => 'Ring File', 16 => 'Ball Pen', 17 => 'Pencil', 18 => 'Eraser', 19 => 'Punch Machine',
                20 => 'Mouse', 21 => 'Keyboard', 22 => 'AA Battery', 23 => 'AAA Battery', 24 => 'Hard Board',
                25 => 'Permanent Marker Pen', 26 => 'White Board Maker', 27 => 'Duster', 28 => 'Hilight pan',
                29 => 'Super Glue', 30 => 'Paper Sticky Glue', 31 => 'Pencil katar', 32 => 'Paper File',
                33 => 'Smc Orsaline', 34 => 'Vomor', 35 => 'Door Lock', 36 => 'Both side tape',
            ]],
            'GS' => ['General Store / Utility', [
                1 => 'Generator Fuel', 2 => 'Cleaning Cloth', 3 => 'Mop', 4 => 'Bucket', 5 => 'Detergent',
                6 => 'Gloves', 7 => 'Mask', 8 => 'Safety Shoes', 9 => 'First Aid Box', 10 => 'Fire Extinguisher',
                11 => 'Air Freshener', 12 => 'Led Light', 13 => 'Electric Cable',
            ]],
            'FA' => ['Buttons & Etc', [
                1 => 'Plastic Button', 2 => 'Metal Button', 3 => 'Snap Button', 4 => 'Tpt Button',
                5 => 'Zipper (Nylon Coil)', 6 => 'Zipper (Metal)', 7 => 'Zipper (Invisible)', 8 => 'Hook & Eye',
                9 => 'Buckle', 10 => 'Shank button',
            ]],
            'JQ' => ['Jacquared', [
                1 => 'Needle', 2 => 'Needle big head', 3 => 'Needle Jack', 4 => 'Selector (No-01)',
                5 => 'Selector (No-02)', 6 => 'Selector (No-03)', 7 => 'Selector (No-04)', 8 => 'Selector (No-05)',
                9 => 'Selector (No-06)', 10 => 'Selector (No-07)', 11 => 'Selector (No-08)', 12 => 'Spring Jack',
                13 => 'Brush', 14 => 'Yarn Storage Support', 15 => 'Main Motor Belt', 16 => 'Feed Wheel Motor Belt',
                17 => 'Racking Sensor', 18 => 'Main Roller -Motor Chain', 19 => 'Stitch Cam -Left side',
                20 => 'Stitch cam -Right side', 21 => 'Feeder Kan-Left Side', 22 => 'Feeder Kan-Right Side',
                23 => 'Feeder Carrier', 24 => 'Feeder Nose', 25 => 'Stitch Lever -Left Side',
                26 => 'Stitch lever -Right side', 27 => 'Mending Needle', 28 => 'Door Rubber lock', 29 => 'Magnet',
                30 => 'Stitch Motor', 31 => 'Limit Sensor', 32 => '8/10 dall', 33 => 'L-key (8 no)',
                34 => 'L-key (6 no)', 35 => 'L-key (5 no)', 36 => 'Power Supply', 37 => 'Selector Box',
                38 => 'Bearing 6005-2RS', 39 => 'Bearing 6002-2R5', 40 => 'Bearing 6000-2RS', 41 => 'Bearing 6804',
                42 => 'Bearing 6801', 43 => 'Bearing 607-ZZ', 44 => 'Bearing 627', 45 => 'Bearing 608-ZZ',
                46 => 'Small Sinker',
            ]],
            'DC' => ['Decorative Trims', [
                1 => 'Lace', 2 => 'Sequin', 3 => 'Beads', 4 => 'Stone / Rhinestone', 5 => 'Embroidery Patch',
                6 => 'Appliqué', 7 => 'Frill', 8 => 'Ribbon',
            ]],
        ];
    }
}
