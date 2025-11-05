<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        "key",
        "value",
        "frequency",
    ];

    public function employees()
    {
        return $this->hasMany(EmployeeSetting::class);
    }
}
