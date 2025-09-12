<?php

namespace App\Http\Resources;

use App\Services\EmployeeService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeWithBalanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "WithUndertime" => $this->WithUndertime,
            "activation_status" => $this->activation_status,
            "allow_bid" => $this->allow_bid,
            "birthdate" => $this->birthdate,
            "birthplace" => $this->birthplace,
            "blood_type" => $this->blood_type,
            "created" => $this->created,
            "createdby" => $this->createdby,
            "dept_code" => $this->dept_code,
            "emp_status" => $this->emp_status,
            "employeeid" => $this->employeeid,
            "employment_code" => $this->employment_code,
            "firstname" => $this->firstname,
            "gender" => $this->gender,
            "lastname" => $this->lastname,
            "maritalstatus" => $this->maritalstatus,
            "middlename" => $this->middlename,
            "modified" => $this->modified,
            "modifiedby" => $this->modifiedby,
            "nationality" => $this->nationality,
            "photoid" => $this->photoid,
            "photopath" => $this->photopath,
            "profilepic" => $this->profilepic,
            "suffix" => $this->suffix,
            "current_fuel_balance" => EmployeeService::getCurrentBalance($this->employeeid),
        ];
    }
}
