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
            'start_at' => 'nullable|date|date_format:"Y-m-d"',
            'end_at' => 'nullable|date|date_format:"Y-m-d"',
            'employees' => 'nullable|array|exists:App\Models\User,id',
            'groupBy' => 'nullable'
        ]);

        $reportsService = app(\App\Services\ReportsService::class);

        // if not start_at, end_at set than use start of month
        $start_at = !empty($validated['start_at']) ? Carbon::createFromFormat('Y-m-d',  $validated['start_at']) : now()->copy()->startOfMonth();
        $end_at = !empty($validated['end_at']) ? Carbon::createFromFormat('Y-m-d', $validated['end_at']) : now()->copy()->endOfMonth();
        
        $availableGroupBy = ['day', 'week', 'month', 'year'];
        $groupBy = isset($validated['groupBy']) ? $validated['groupBy'] : 'day';

        //TODO check if manager through employeer get access to employees
        $access_to_employees = \Auth::user()->employees(\App\Models\User::ACCEPTED)->get()->reject(function ($u) use($validated) {
            return count($validated['employees'] ?? [])
                ? !in_array($u->id, $validated['employees'])
                : false;
        });
        $employee_ids = $access_to_employees->pluck('name', 'id');
        $employer = \Auth::user();

        $prev_period_data = $reportsService->getProductivityAnalytics(
            $employer->id, $employee_ids->keys()->toArray(), $start_at, $end_at, $groupBy
        );

        $current_period_data = $reportsService->getProductivityAnalytics(
            $employer->id, $employee_ids->keys()->toArray(), $start_at, $end_at, $groupBy
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

        $key = array_search($groupBy, $availableGroupBy);

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

            'categories' => $current_period_data->pluck(
                $availableGroupBy[$key]
            ),
            'prev_period_data' => [
                'productive_secs' => $prev_period_data->pluck('productive_secs'),
                'neutral_secs' => $prev_period_data->pluck('neutral_secs'),
                'non_productive_secs' => $prev_period_data->pluck('non_productive_secs'),
            ],
            'current_period_data' => [
                'productive_secs' => $current_period_data->pluck('productive_secs'),
                'neutral_secs' => $current_period_data->pluck('neutral_secs'),
                'non_productive_secs' => $current_period_data->pluck('non_productive_secs'),
            ],
        ]);
    }

    public function employees(Request $request) {
        $validated = $request->validate([
            'start_at' => 'nullable|date|date_format:"Y-m-d"',
            'end_at' => 'nullable|date|date_format:"Y-m-d"',
            'employees' => 'nullable|array|exists:App\Models\User,id',
            'groupBy' => 'nullable'
        ]);

        $reportsService = app(\App\Services\ReportsService::class);

        // if not start_at, end_at set than use start of month
        $start_at = !empty($validated['start_at']) ? Carbon::createFromFormat('Y-m-d',  $validated['start_at']) : now()->copy()->startOfMonth();
        $end_at = !empty($validated['end_at']) ? Carbon::createFromFormat('Y-m-d', $validated['end_at']) : now()->copy()->endOfMonth();
        
        $availableGroupBy = ['day', 'week', 'month', 'year'];
        $groupBy = isset($validated['groupBy']) ? $validated['groupBy'] : 'day';

        //TODO check if manager through employeer get access to employees
        $access_to_employees = \Auth::user()->employees(\App\Models\User::ACCEPTED)->get()->reject(function ($u) use($validated) {
            return count($validated['employees'] ?? [])
                ? !in_array($u->id, $validated['employees'])
                : false;
        });
        $employee_ids = $access_to_employees->pluck('name', 'id');
        $employer = \Auth::user();

        $for_categories = $reportsService->getProductivityAnalytics(
            $employer->id, $employee_ids->keys()->toArray(), $start_at, $end_at, $groupBy
        );

        $users_analytics = $reportsService->getProductivityAnalytics(
            $employer->id, $employee_ids->keys()->toArray(), $start_at, $end_at, [$groupBy, 'user_id']
        );

        $key = array_search($groupBy, $availableGroupBy);

        return response()->json([
            'categories' => $for_categories->pluck(
                $availableGroupBy[$key]
            ),
            'users' => $users_analytics->pluck('name', 'user_id'),
            'analytics' => $users_analytics->groupBy(['user_id', $groupBy])
        ]);

    }

    public function topApps(Request $request) {
        $validated = $request->validate([
            'start_at' => 'nullable|date|date_format:"Y-m-d"',
            'end_at' => 'nullable|date|date_format:"Y-m-d"',
            'employees' => 'nullable|array|exists:App\Models\User,id',
        ]);

        $reportsService = app(\App\Services\ReportsService::class);

        // if not start_at, end_at set than use start of month
        $start_at = !empty($validated['start_at']) ? Carbon::createFromFormat('Y-m-d',  $validated['start_at']) : now()->copy()->startOfMonth();
        $end_at = !empty($validated['end_at']) ? Carbon::createFromFormat('Y-m-d', $validated['end_at']) : now()->copy()->endOfMonth();
        
        //TODO check if manager through employeer get access to employees
        $access_to_employees = \Auth::user()->employees(\App\Models\User::ACCEPTED)->get()->reject(function ($u) use($validated) {
            return count($validated['employees'] ?? [])
                ? !in_array($u->id, $validated['employees'])
                : false;
        });
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
            'start_at' => 'nullable|date|date_format:"Y-m-d"',
            'end_at' => 'nullable|date|date_format:"Y-m-d"',
            'employees' => 'nullable|array|exists:App\Models\User,id',
        ]);

        $reportsService = app(\App\Services\ReportsService::class);

        // if not start_at, end_at set than use start of year
        $start_at = !empty($validated['start_at']) ? Carbon::createFromFormat('Y-m-d',  $validated['start_at']) : now()->copy()->startOfYear();
        $end_at = !empty($validated['end_at']) ? Carbon::createFromFormat('Y-m-d', $validated['end_at']) : now()->copy()->endOfYear();
        
        //TODO check if manager through employeer get access to employees
        $access_to_employees = \Auth::user()->employees(\App\Models\User::ACCEPTED)->get()->reject(function ($u) use($validated) {
            return count($validated['employees'] ?? [])
                ? !in_array($u->id, $validated['employees'])
                : false;
        });
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
