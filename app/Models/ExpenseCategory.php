<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{

    protected $fillable = [
        'user_id', 'description', 'name', 'account_id', 'created_at', 'updated_at', 'deleted_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'account_id' => 'integer',
    ];

    public function account()
    {
        return $this->belongsTo('App\Models\Account');
    }

    public function expense()
    {
        return $this->belongsTo('App\Models\Expense');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

}
