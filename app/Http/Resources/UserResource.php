<?php

namespace App\Http\Resources;

use App\Services\EmployeeService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

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
        $isManagerial = null;

        if($this->employeeid){
            $employee = EmployeeService::fetchActiveEmployee($this->employeeid);

            $isManagerial = (
                $employee->WithUndertime === "N" ||
                stripos($employee->desig_position, 'manager') !== false ||
                stripos($employee->desig_position, 'supervisor') !== false
            );
        }


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
            "WithUndertime" => $employee->WithUndertime ?? null,
            "desig_position" => $employee->desig_position ?? null,
            "dept_code" => $employee->dept_code ?? null,
            "div_code" => $employee->div_code ?? null,
            "firstname" => $employee->firstname ?? null,
            "gender" => $employee->gender ?? null,
            "lastname" => $employee->lastname ?? null,
            "middlename" => $employee->middlename ?? null,
            "suffix" => $employee->suffix ?? null,
            "isManagerial" => $isManagerial,
            "roles" => $this->getRoleNames(),
            "permissions" => $this->getAllPermissions()->pluck('name'),
        ];
    }
}
