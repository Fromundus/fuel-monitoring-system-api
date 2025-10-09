<?php

namespace App\Http\Resources;

use App\Services\EmployeeService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $employee = null;

        if($this->employeeid){
            $employee = EmployeeService::fetchActiveEmployee($this->employeeid);
        }

        $isManagerial = (
            $employee->WithUndertime === "N" ||
            stripos($employee->desig_position, 'manager') !== false ||
            stripos($employee->desig_position, 'supervisor') !== false
        );

        return [
            "created_at" => $this->created_at,
            "email" => $this->email,
            "email_verified_at" => $this->email_verified_at,
            "employeeid" => $this->employeeid,
            "id" => $this->id,
            "name" => $this->name,
            "role" => $this->role,
            "status" => $this->status,
            "updated_at" => $this->updated_at,
            "username" => $this->username,
            "WithUndertime" => $employee->WithUndertime,
            "desig_position" => $employee->desig_position,
            "dept_code" => $employee->dept_code,
            "div_code" => $employee->div_code,
            "firstname" => $employee->firstname,
            "gender" => $employee->gender,
            "lastname" => $employee->lastname,
            "middlename" => $employee->middlename,
            "suffix" => $employee->suffix,
            "isManagerial" => $isManagerial,

        ];
    }
}
