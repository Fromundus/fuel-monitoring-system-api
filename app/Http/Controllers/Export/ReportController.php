<?php

namespace App\Http\Controllers\Export;

use App\Exports\RequestsReportExport;
use App\Http\Controllers\Controller;
use App\Models\Request as ModelsRequest;
use App\Services\BalanceWarehouseService;
use App\Services\EmployeeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    // public function exportRequests(Request $request)
    // {
    //     $types = (array) $request->query('type', []);
    //     $statuses = (array) $request->query('status', []);
    //     $fuelTypes = (array) $request->query('fuel_type', []);
    //     $sources = (array) $request->query('source', []);
    //     $purposes = (array) $request->query('purpose', []);

    //     // Apply your same filtering logic here
    //     $query = ModelsRequest::query();

    //     if (!empty($types) && !in_array('all', $types)) {
    //         $query->whereIn('type', $types);
    //     }

    //     if (!empty($statuses) && !in_array('all', $statuses)) {
    //         $query->whereIn('status', $statuses);
    //     }

    //     if (!empty($fuelTypes) && !in_array('all', $fuelTypes)) {
    //         $query->whereIn('fuel_type', $fuelTypes);
    //     }

    //     if (!empty($sources) && !in_array('all', $sources)) {
    //         $query->whereIn('source_id', $sources);
    //     }

    //     if (!empty($purposes) && !in_array('all', $purposes)) {
    //         $query->whereIn('purpose_id', $purposes);
    //     }

    //     $requests = $query->get();

    //     return \Maatwebsite\Excel\Facades\Excel::download(
    //         new \App\Exports\RequestsReportExport($requests),
    //         'requests-report.xlsx'
    //     );
    // }

    // public function exportRequests()
    // {
    //     $requests = ModelsRequest::all();

    //     return Excel::download(
    //         new RequestsReportExport($requests),
    //         'fuel_requests_report.xlsx'
    //     );
    // }

    // public function exportRequests()
    // {
    //     $templatePath = storage_path('app/templates/request_report.xlsx');

    //     // Load the template
    //     $spreadsheet = IOFactory::load($templatePath);
    //     $sheet = $spreadsheet->getActiveSheet();

    //     // Fetch data from DB
    //     $requests = ModelsRequest::orderBy('date', 'asc')
    //         ->get();

    //     // Set starting row (example row 10, where your table begins)
    //     $startRow = 11;
    //     $row = $startRow;

    //     foreach ($requests as $req) {
    //         $date = Carbon::parse($req->date)->format('m/d/y');

    //         $employee = EmployeeService::fetchActiveEmployee($req->employeeid);

    //         $flfn = substr($employee->firstname, 0, 1);
    //         $flmd = substr($employee->middlename, 0, 1);
    //         $ln = $employee->lastname;

    //         $name = "{$flfn}. {$flmd}. {$ln}";

    //         $sheet->setCellValue("A{$row}", $date);
    //         $sheet->setCellValue("B{$row}", optional($req->vehicle)->plate_no);
    //         $sheet->setCellValue("C{$row}", $name);
    //         $sheet->setCellValue("D{$row}", $req->reference_number);
    //         $sheet->setCellValue("E{$row}", $req->purpose);
    //         $sheet->setCellValue("F{$row}", $req->quantity);

    //         $row++;
    //     }

    //     // Return file as download
    //     return new StreamedResponse(function () use ($spreadsheet) {
    //         $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    //         $writer->save('php://output');
    //     }, 200, [
    //         "Content-Type" => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    //         "Content-Disposition" => "attachment; filename=generated_report.xlsx"
    //     ]);
    // }

    public function exportRequests(Request $request)
    {
        $templatePath = storage_path('app/templates/request_report.xlsx');

        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        $types = (array) $request->query('type', []);
        $statuses = (array) $request->query('status', []);
        $fuel_type = $request->query('fuel_type', 'all');
        $sources = (array) $request->query('source', []);
        $purposes = (array) $request->query('purpose', []);

        $sheet->setCellValue("A6", "WAREHOUSE SUMMARY REPORT OF {$fuel_type}");
        $sheet->setCellValue("A8", Carbon::now()->format('M d, Y'));

        // Apply your same filtering logic here
        $query = ModelsRequest::query();

        if (!empty($types) && !in_array('all', $types)) {
            $query->whereIn('type', $types);
        }

        if (!empty($statuses) && !in_array('all', $statuses)) {
            $query->whereIn('status', $statuses);
        }

        if($fuel_type && $fuel_type !== 'all'){
            $query->where('fuel_type', $fuel_type);
        }

        if (!empty($sources) && !in_array('all', $sources)) {
            $query->whereIn('source_id', $sources);
        }

        if (!empty($purposes) && !in_array('all', $purposes)) {
            $query->whereIn('purpose_id', $purposes);
        }

        $requests = $query->get();
        $requestsCount = $query->count();
        $firstRequest = $query->first();

        $startRow = 11; // where your first data row begins
        
        foreach ($requests as $index => $req) {

            // For every row AFTER the first, insert new rows
            if ($index > 0) {
                $sheet->insertNewRowBefore($startRow + $index, 1);
            }

            $currentRow = $startRow + $index;

            // Format date
            $date = Carbon::parse($req->date)->format('m/d/Y');

            // Format name → first letters
            $employee = EmployeeService::fetchActiveEmployee($req->employeeid);
            $flfn = substr($employee->firstname, 0, 1);
            $flmd = substr($employee->middlename, 0, 1);
            $ln = $employee->lastname;
            $s = $employee->suffix;
            $name = "{$flfn}. {$flmd}. {$ln} {$s}";
            
            // Populate cells
            $sheet->setCellValue("A{$currentRow}", $date);
            $sheet->setCellValue("B{$currentRow}", optional($req->vehicle)->plate_no);
            $sheet->setCellValue("C{$currentRow}", $name);
            $sheet->setCellValue("D{$currentRow}", $req->reference_number);
            $sheet->setCellValue("E{$currentRow}", $req->purpose);
            $sheet->setCellValue("F{$currentRow}", $req->quantity);
        }

        $totalIssuedCell = ($startRow + $requestsCount);
        $totalIssuedFormulaCell = $totalIssuedCell - 1;
        $totalIssuedFormula = "=SUM(F11:F{$totalIssuedFormulaCell})";

        $sheet->setCellValue("F8", BalanceWarehouseService::getItemBalance($firstRequest->fuel_type_id));
        $sheet->setCellValue("A{$totalIssuedCell}", "TOTAL ISSUED OF {$fuel_type}");
        $sheet->setCellValue("F{$totalIssuedCell}", $totalIssuedFormula);
        
        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
        }, 200, [
            "Content-Type" => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            "Content-Disposition" => "attachment; filename=generated_report.xlsx"
        ]);
    }


}
