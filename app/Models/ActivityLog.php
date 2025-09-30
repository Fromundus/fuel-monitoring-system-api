<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        "request_id",
        "user_id",
        "employee_id",

        "action",
        "description",

        "item_id",
        "item_name",
        "item_unit",
        "quantity",

        "reference_number",
        "reference_type",
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function request(){
        return $this->belongsTo(User::class);
    }
}
