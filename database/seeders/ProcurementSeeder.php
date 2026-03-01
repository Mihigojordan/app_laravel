<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProcurementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $keys = [
            'Procurement' => 'Procurement',
            'Requisitions' => 'Requisitions',
            'CreateRequisition' => 'Create Requisition',
            'ListRequisitions' => 'List Requisitions',
            'CreatePurchaseOrder' => 'Create Purchase Order',
            'ListPurchaseOrders' => 'List Purchase Orders',
            'PurchaseOrder' => 'Purchase Order',
            'PurchaseOrders' => 'Purchase Orders',
        ];

        foreach ($keys as $label => $translation) {
            // Check if key exists for English (label language_id = 1)
            $exists = DB::table('languages')->where('label', $label)->where('language_id', 1)->exists();
            
            if (!$exists) {
                DB::table('languages')->insert([
                    'label' => $label,
                    'translation' => $translation,
                    'language_id' => 1, // English
                ]);
            } else {
                DB::table('languages')->where('label', $label)->where('language_id', 1)->update([
                    'translation' => $translation,
                ]);
            }
        }
    }
}
