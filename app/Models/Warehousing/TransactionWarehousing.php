<?php

namespace App\Models\Warehousing;

use Illuminate\Database\Eloquent\Model;

class TransactionWarehousing extends Model
{
    protected $connection = 'mysql3';
    protected $table = 'transactions';

    protected $fillable = [
        'id',
        'ItemID',
        'ReferenceNo',
        'ReferenceType',
        'TransactionType',
        'Quantity',
        'Remarks',
        'CreatedBy',
        'CreatedOn',
    ];
}
