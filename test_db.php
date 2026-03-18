<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Requisition;
use App\Models\RequisitionDetail;
use Carbon\Carbon;

try {
    \DB::transaction(function () {
        $requisition = new Requisition;
        $requisition->date = date('Y-m-d');
        $requisition->Ref = 'TEST_' . time();
        $requisition->warehouse_id = 1; // Assuming warehouse 1 exists
        $requisition->status = 'pending';
        $requisition->user_id = 1; // Assuming user 1 exists
        $requisition->save();
        echo "Requisition saved with ID: " . $requisition->id . "\n";

        $orderDetails[] = [
            'requisition_id' => $requisition->id,
            'quantity' => 1,
            'product_id' => 1, // Assuming product 1 exists
            'product_variant_id' => null,
            'unit_id' => 1, // Assuming unit 1 exists
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
        
        RequisitionDetail::insert($orderDetails);
        echo "RequisitionDetail inserted.\n";
    });
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    // echo $e->getTraceAsString();
}
