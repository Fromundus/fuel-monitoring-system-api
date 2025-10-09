<?php

namespace App\Http\Resources;

use App\Services\AllowanceService;
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
        // $latest_fuel_period = EmployeeService::getLatestBalance($this->employeeid, 'gasoline-diesel');
        // $latest_oil_period = EmployeeService::getLatestBalance($this->employeeid, '4t2t');
        // $latest_fluid_period = EmployeeService::getLatestBalance($this->employeeid, 'bfluid');
        
        $latest_fuel_period = AllowanceService::getLatestGrantedBalanceRow($this->employeeid, 'gasoline-diesel');
        $latest_oil_period = AllowanceService::getLatestGrantedBalanceRow($this->employeeid, '2t4t');
        $latest_fluid_period = AllowanceService::getLatestGrantedBalanceRow($this->employeeid, 'b-fluid');

        $isManagerial = (
            $this->WithUndertime === "N" ||
            stripos($this->desig_position, 'manager') !== false ||
            stripos($this->desig_position, 'supervisor') !== false
        );
        
        return [
            "WithUndertime" => $this->WithUndertime,
            "desig_position" => $this->desig_position,
            // "activation_status" => $this->activation_status,
            // "allow_bid" => $this->allow_bid,
            // "birthdate" => $this->birthdate,
            // "birthplace" => $this->birthplace,
            // "blood_type" => $this->blood_type,
            // "created" => $this->created,
            // "createdby" => $this->createdby,
            "dept_code" => $this->dept_code,
            "div_code" => $this->div_code,
            // "emp_status" => $this->emp_status,
            "employeeid" => $this->employeeid,
            // "employment_code" => $this->employment_code,
            "firstname" => $this->firstname,
            "gender" => $this->gender,
            "lastname" => $this->lastname,
            // "maritalstatus" => $this->maritalstatus,
            "middlename" => $this->middlename,
            // "modified" => $this->modified,
            // "modifiedby" => $this->modifiedby,
            // "nationality" => $this->nationality,
            // "photoid" => $this->photoid,
            // "photopath" => $this->photopath,
            // "profilepic" => $this->profilepic,
            "suffix" => $this->suffix,

            "current_fuel_balance" => AllowanceService::getBalance($this->employeeid, 'gasoline-diesel'),
            "current_fuel_period" => $latest_fuel_period["granted_at"] ?? null,
            
            "current_oil_balance" => AllowanceService::getBalance($this->employeeid, '2t4t'),
            "current_oil_period" => $latest_oil_period["granted_at"] ?? null,
            
            "current_fluid_balance" => AllowanceService::getBalance($this->employeeid, 'b-fluid'),
            "current_fluid_period" => $latest_fluid_period["granted_at"] ?? null,

            "total_distance_travelled" => EmployeeService::getTotalDistanceTravelled($this->employeeid),
            "current_oil_tripticket_balance" => AllowanceService::getBalance($this->employeeid, 'trip-ticket'),

            "distance_travelled_since_last" => EmployeeService::getDistanceSinceLastIssue($this->employeeid),

            "isManagerial" => $isManagerial,
        ];
    }
}
