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
        ]);

        $start_of_date = Carbon::createFromFormat('Y-m-d', $validated['start_at']);
        $end_of_date = Carbon::createFromFormat('Y-m-d', $validated['end_at']);

        $access_to_employees = \Auth::user()->employees();
        $employee_ids = $access_to_employees->pluck('name', 'id');
        $employer = \Auth::user();

        $current_metrics_data = $reportsService->getProductivityAnalytics(
            $employer->id, $employee_ids->keys()->toArray(), ['*'], $start_of_date, $end_of_date
        )->groupBy('project_id')->map(function ($data) {
            return ['users' => $data->groupBy('user_id')->map(function ($data) { return $data->first(); })];
        });

        return response()->json([
            'data' => $current_metrics_data
        ]);
    }

    public function burnout(Request $request) {
        //
    }
}
