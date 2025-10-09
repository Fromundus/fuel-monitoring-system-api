<?php

namespace App\Models\Warehousing;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $connection = 'mysql3';
    protected $table = 'items';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'InventoryTypeID',
        'ItemCode',
        'ItemName',
        'ItemTypeID',
        'UnitID',
        'IsInventoriable',
        'UnitCost',
        'AverageCost',
        'NetOfVat',
        'TurnOnAmount',
        'QuantityOnHand',
        'CreatedBy',
        'CreatedOn',
    ];

    public function unit(){
        return $this->hasOne(Unit::class, 'id', 'UnitID');
    }
}