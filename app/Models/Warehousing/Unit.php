<?php

namespace App\Models\Warehousing;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $connection = 'mysql3';
    protected $table = 'unit';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'unitCode',
        'unitName',
        'CreatedBy',
        'CreatedOn',
    ];
}