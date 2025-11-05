<?php

namespace App\Models;

use App\Services\EmployeeService;
use Illuminate\Database\Eloquent\Model;

class EmployeeSetting extends Model
{
    protected $appends = ['employee'];

    protected $fillable = [
        'employee_id',
        'setting_id',
        'active',
    ];

    public function setting()
    {
        return $this->belongsTo(Setting::class);
    }

    // Optional helper to fetch employee info from external DB
    public function getEmployeeAttribute()
    {
        return EmployeeService::fetchActiveEmployee($this->employee_id);
    }
}
