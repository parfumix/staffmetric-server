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

        // if not start_at, end_at set than use start of year
        $start_at = !empty($validated['start_at']) ? Carbon::createFromFormat('Y-m-d',  $validated['start_at']) : now()->copy()->startOfYear();
        $end_at = !empty($validated['end_at']) ? Carbon::createFromFormat('Y-m-d', $validated['end_at']) : now()->copy()->endOfYear();
        
        $groupBy = isset($validated['groupBy']) ? $validated['groupBy'] : false;

        // set group by param
        if(!$groupBy) {
            //TODO if range is bigger than a week set range to days
            //TODO if range is bigger than a month set range to weeks
            //TODO if range is bigger than a 3 to 6 months set rante to months
            //TODO if range is bigger than 6 months set range to months
        }

        //TODO check if manager through employeer get access to employees
        $access_to_employees = \Auth::user()->employees;
        $employee_ids = $access_to_employees->pluck('name', 'id');
        $employer = \Auth::user();

        $prev_period_data = $reportsService->getProductivityAnalytics(
            $employer->id, $employee_ids->keys()->toArray(), $start_at, $end_at
        );

        $current_period_data = $reportsService->getProductivityAnalytics(
            $employer->id, $employee_ids->keys()->toArray(), $start_at, $end_at
        );

        // calculate current metrics
        $current_productive_secs = $current_period_data->pluck('productive_secs')->sum();
        $current_non_productive_secs = $current_period_data->pluck('non_productive_secs')->sum();
        $current_neutral_secs = $current_period_data->pluck('neutral_secs')->sum();
        $current_total_secs = $current_productive_secs + $current_non_productive_secs + $current_neutral_secs;


         // calculate prev metrics
         $prev_productive_secs = $prev_period_data->pluck('productive_secs')->sum();
         $prev_non_productive_secs = $prev_period_data->pluck('non_productive_secs')->sum();
         $prev_neutral_secs = $prev_period_data->pluck('neutral_secs')->sum();
         $prev_total_secs = $prev_productive_secs + $prev_non_productive_secs + $prev_neutral_secs;

        return response()->json([
            'total_secs' => [
                'current' => $current_total_secs,
                'prev' => $prev_total_secs,
            ],
            'productive_secs' => [
                'current' => $current_productive_secs,
                'prev' => $prev_productive_secs,
            ],
            'non_productive_secs' => [
                'current' => $current_non_productive_secs,
                'prev' => $prev_non_productive_secs,
            ],

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

    public function employees(Request $request) {
        //
    }

    public function topApps(Request $request) {
        $validated = $request->validate([
            'start_at' => 'nullable|date_format:"Y-m-d"',
            'end_at' => 'nullable|date_format:"Y-m-d"',
        ]);

        $reportsService = app(\App\Services\ReportsService::class);

        // if not start_at, end_at set than use start of year
        $start_at = !empty($validated['start_at']) ? Carbon::createFromFormat('Y-m-d',  $validated['start_at']) : now()->copy()->startOfYear();
        $end_at = !empty($validated['end_at']) ? Carbon::createFromFormat('Y-m-d', $validated['end_at']) : now()->copy()->endOfYear();
        
        //TODO check if manager through employeer get access to employees
        $access_to_employees = \Auth::user()->employees;
        $employee_ids = $access_to_employees->pluck('name', 'id');
        $employer = \Auth::user();

        $data = $reportsService->getTopApps(
            $employee_ids->keys()->toArray(), $start_at, $end_at
        );

        return response()->json([
            'data' => $data
        ]);
    }

    public function topCategories(Request $request) {
        $validated = $request->validate([
            'start_at' => 'nullable|date_format:"Y-m-d"',
            'end_at' => 'nullable|date_format:"Y-m-d"',
        ]);

        $reportsService = app(\App\Services\ReportsService::class);

        // if not start_at, end_at set than use start of year
        $start_at = !empty($validated['start_at']) ? Carbon::createFromFormat('Y-m-d',  $validated['start_at']) : now()->copy()->startOfYear();
        $end_at = !empty($validated['end_at']) ? Carbon::createFromFormat('Y-m-d', $validated['end_at']) : now()->copy()->endOfYear();
        
        //TODO check if manager through employeer get access to employees
        $access_to_employees = \Auth::user()->employees;
        $employee_ids = $access_to_employees->pluck('name', 'id');
        $employer = \Auth::user();

        $data = $reportsService->getTopCategories(
            $employee_ids->keys()->toArray(), $start_at, $end_at
        );

        return response()->json([
            'data' => $data
        ]);
    }
}
