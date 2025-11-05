<?php

namespace App\Models\Payroll;

use App\Models\EmployeeAllowance;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $connection = "mysql2";
    protected $table = "employee";

    public function allowances(){
        return $this->hasMany(EmployeeAllowance::class, 'employee_id', 'employeeid');
    }
}
