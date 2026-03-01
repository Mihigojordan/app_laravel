<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequisitionDetail extends Model
{
    protected $fillable = [
        'requisition_id', 'product_id', 'product_variant_id', 'quantity', 'unit_id',
    ];

    protected $casts = [
        'requisition_id' => 'integer',
        'product_id' => 'integer',
        'product_variant_id' => 'integer',
        'unit_id' => 'integer',
        'quantity' => 'double',
    ];

    public function requisition()
    {
        return $this->belongsTo('App\Models\Requisition');
    }

    public function product()
    {
        return $this->belongsTo('App\Models\Product');
    }

    public function unit()
    {
        return $this->belongsTo('App\Models\Unit');
    }
}
