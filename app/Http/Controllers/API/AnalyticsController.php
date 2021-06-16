<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ReportsService;
use Carbon\Carbon;

class AnalyticsController extends Controller {
    
    /**
     * Reports by productivity
     * 
     */
    public function productivity(Request $request, ReportsService $reportsService) {
        $validated = $request->validate([
            'start_at' => 'nullable|date_format:"Y-m-d"',
            'end_at' => 'nullable|date_format:"Y-m-d"',
            'groupBy' => 'nullable'
        ]);

        $start_of_date = Carbon::createFromFormat('Y-m-d', $validated['start_at']);
        $end_of_date = Carbon::createFromFormat('Y-m-d', $validated['end_at']);

        //TODO check if manager through employeer get access to employees
        $access_to_employees = \Auth::user()->employees();
        $employee_ids = $access_to_employees->pluck('name', 'id');
        $employer = \Auth::user();

        
        $prev_period_dta = $reportsService->getProductivityAnalytics(
            $employer->id, $employee_ids->keys()->toArray(), $start_of_date, $end_of_date
        );

        $current_period_data = $reportsService->getProductivityAnalytics(
            $employer->id, $employee_ids->keys()->toArray(), $start_of_date, $end_of_date
        );

        return response()->json([
            'prev_period_dta' => $prev_period_dta,
            'current_period_data' => $current_period_data
        ]);
    }

    public function burnout(Request $request) {
        //
    }
}
