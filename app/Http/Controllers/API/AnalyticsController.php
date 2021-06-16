<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AnalyticsController extends Controller {
    
    /**
     * Reports by productivity
     * 
     */
    public function productivity(Request $request) {
        $validated = $request->validate([
            'start_at' => 'nullable|date_format:"Y-m-d"',
            'end_at' => 'nullable|date_format:"Y-m-d"',
            'groupBy' => 'nullable'
        ]);

        $reportsService = app(\App\Services\ReportsService::class);

        $start_at = $validated['start_at'] ?? now()->copy()->startOfYear()->format('Y-m-d');
        $end_at = $validated['end_at'] ?? now()->copy()->endOfYear()->format('Y-m-d');
        
        $start_of_date = Carbon::createFromFormat('Y-m-d', $start_at);
        $end_of_date = Carbon::createFromFormat('Y-m-d', $end_at);

        //TODO check if manager through employeer get access to employees
        $access_to_employees = \Auth::user()->employees;
        $employee_ids = $access_to_employees->pluck('name', 'id');
        $employer = \Auth::user();

        $prev_period_data = $reportsService->getProductivityAnalytics(
            $employer->id, $employee_ids->keys()->toArray(), $start_of_date, $end_of_date
        );

        $current_period_data = $reportsService->getProductivityAnalytics(
            $employer->id, $employee_ids->keys()->toArray(), $start_of_date, $end_of_date
        );

        return response()->json([
            'categories' => $current_period_data->pluck('month'),
            'prev_period_data' => [
                'productive_secs' => $prev_period_data->pluck('productive_secs'),
                'neutral_secs' => $prev_period_data->pluck('neutral_secs'),
                'non_productive_secs' => $prev_period_data->pluck('non_productive_secs'),
            ],
            'current_period_data' => [
                'productive_secs' => $prev_period_data->pluck('productive_secs'),
                'neutral_secs' => $prev_period_data->pluck('neutral_secs'),
                'non_productive_secs' => $prev_period_data->pluck('non_productive_secs'),
            ],
        ]);
    }

    public function burnout(Request $request) {
        //
    }
}
