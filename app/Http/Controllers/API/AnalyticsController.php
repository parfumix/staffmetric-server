<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AnalyticsController extends Controller {
    
    public function productivity(Request $request) {
        $validated = $request->validate([
            'start_at' => 'nullable|date|date_format:"Y-m-d"',
            'end_at' => 'nullable|date|date_format:"Y-m-d"',
            'employees' => 'nullable|array|exists:App\Models\User,id',
            'groupBy' => 'nullable',
            'fields' => 'nullable|array'
        ]);

        $fields_to_select = $validated['fields'] ?? 
            ['email_secs', 'social_network_secs', 'productive_secs', 'neutral_secs', 'non_productive_secs', 'total_secs'];

        $reportsService = app(\App\Services\ReportsService::class);

        // if not start_at, end_at set than use start of month
        $start_at = !empty($validated['start_at']) ? Carbon::createFromFormat('Y-m-d',  $validated['start_at']) : now()->copy()->startOfMonth();
        $end_at = !empty($validated['end_at']) ? Carbon::createFromFormat('Y-m-d', $validated['end_at']) : now()->copy()->endOfMonth();
        
        $groupBy = isset($validated['groupBy']) ? $validated['groupBy'] : 'day';
        $dateRanges = $groupBy == 'hour'
            ? hours_range(9, 20, 'H')
            : generate_date_range($start_at, $end_at, $groupBy);

        //calculate prev time
        [$prev_start_at, $prev_end_at] = generate_prev_date($start_at, $end_at, $groupBy);

        $employee_ids = get_access_employees(\Auth::user(), $validated['employees'] ?? []);

        $prev_period_data = $reportsService->getProductivityAnalytics(
            \Auth::id(), $employee_ids->keys()->toArray(), $prev_start_at, $prev_end_at, [$groupBy, 'user_id'], $fields_to_select
        )->groupBy([$groupBy, 'user_id']);

        $current_period_data = $reportsService->getProductivityAnalytics(
            \Auth::id(), $employee_ids->keys()->toArray(), $start_at, $end_at, [$groupBy, 'user_id'], $fields_to_select
        )->groupBy([$groupBy, 'user_id']);

        $formatted_current_data = [];
        $formatted_prev_data = [];
        foreach ($employee_ids->keys() as $employee_id) {
            foreach($dateRanges as $date) {
                $defaultvalues = collect($fields_to_select)->mapWithKeys(function($field) {
                    return [$field => 0];
                })->toArray();

                // build current data
                $value_selected = $current_period_data->get($date, collect([]))->get($employee_id);
                $formatted_current_data[$employee_id][$date] = $value_selected ? (array)$value_selected->first() : $defaultvalues;

                // build prev data
                $prev_value_selected = $prev_period_data->get($date, collect([]))->get($employee_id);
                $formatted_prev_data[$employee_id][$date] = $prev_value_selected ? (array)$prev_value_selected->first() : $defaultvalues;
            }
        }

        $current_period_data = [];
        $prev_period_data = [];
        foreach ($fields_to_select as $field) {
            foreach ($formatted_current_data as $emplyoee_id => $items) {
                $current_period_data[$field][$emplyoee_id] = collect($items)->pluck($field)->map(function($el) { return intval($el); })->toArray();
            }

            foreach ($formatted_prev_data as $emplyoee_id => $prev_items) {
                $prev_period_data[$field][$emplyoee_id] = collect($prev_items)->pluck($field)->map(function($el) { return intval($el); })->toArray();
            }
        }

        return response()->json([
            'categories' => $dateRanges,
            'prev_period_data' => $prev_period_data,
            'current_period_data' => $current_period_data,
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
        
        $groupBy = isset($validated['groupBy']) ? $validated['groupBy'] : 'day';

        $employee_ids = get_access_employees(\Auth::user(), $validated['employees'] ?? []);

        $fields_to_select = ['productive_secs', 'neutral_secs', 'non_productive_secs'];

        $users_analytics = $reportsService->getProductivityAnalytics(
            \Auth::id(), $employee_ids->keys()->toArray(), $start_at, $end_at, [$groupBy, 'user_id'], $fields_to_select
        );

        return response()->json([
            'categories' => generate_date_range($start_at, $end_at, $groupBy),
            'users' => $employee_ids->keys()->reject(function($el) use($validated) { return isset($validated['employees']) && count($validated['employees']) ? !in_array($el, $validated['employees']) : false; }),
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
        
        $employee_ids = get_access_employees(\Auth::user(), $validated['employees'] ?? []);

        $data = $reportsService->getTopApps(
            $employee_ids->keys()->toArray(), $start_at, $end_at, null, 6
        );

        return response()->json([
            'total' => $data->sum('duration'),
            'apps' => $data
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
        
        $employee_ids = get_access_employees(\Auth::user(), $validated['employees'] ?? []);

        $data = $reportsService->getTopCategories(
            $employee_ids->keys()->toArray(), $start_at, $end_at
        );

        return response()->json([
            'data' => $data
        ]);
    }

    public function burnout(Request $request) {
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
        
        $groupBy = isset($validated['groupBy']) ? $validated['groupBy'] : 'day';
        $dateRanges = generate_date_range($start_at, $end_at, $groupBy);
        [$prev_start_at, $prev_end_at] = generate_prev_date($start_at, $end_at, $groupBy);

        $employee_ids = get_access_employees(\Auth::user(), $validated['employees'] ?? []);

        $current_period_data = $reportsService->getBurnoutAnalytics(
            \Auth::id(), $employee_ids->keys()->toArray(), $start_at, $end_at, $groupBy
        )->groupBy($groupBy);

        $prev_period_data = $reportsService->getBurnoutAnalytics(
            \Auth::id(), $employee_ids->keys()->toArray(), $prev_start_at, $prev_end_at, $groupBy
        )->groupBy($groupBy);

        $formatter = function($dateRanges, $data, $defaultValues = ['burnout' => 0, 'engagment' => 0,]) {
            return collect($dateRanges)->map(function($date) use($data, $defaultValues) {
                return $data->get($date) ? $data->get($date)->first() : $defaultValues;
            });
        };

        $formatted_current_data = $formatter($dateRanges, $current_period_data);
        $formatted_prev_data = $formatter($dateRanges, $prev_period_data);

        return response()->json([
            'categories' => $dateRanges,
            'data' => [
                'burnout' => $formatted_current_data->pluck('burnout'),
                'engagment' => $formatted_current_data->pluck('engagment'),
            ],
            'prev' => [
                'burnout' => $formatted_prev_data->pluck('burnout'),
                'engagment' => $formatted_prev_data->pluck('engagment'),
            ],
        ]);
    }

    public function engagmentEmployees(Request $request) {
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
        
        $groupBy = isset($validated['groupBy']) ? $validated['groupBy'] : 'day';

        $employee_ids = get_access_employees(\Auth::user(), $validated['employees'] ?? []);

        $users_analytics = $reportsService->getBurnoutAnalytics(
            \Auth::id(), $employee_ids->keys()->toArray(), $start_at, $end_at, [$groupBy, 'user_id']
        );

        return response()->json([
            'categories' => generate_date_range($start_at, $end_at, $groupBy),
            'users' => $employee_ids->keys()->reject(function($el) use($validated) { return isset($validated['employees']) && count($validated['employees']) ? !in_array($el, $validated['employees']) : false; }),
            'analytics' => $users_analytics->groupBy(['user_id', $groupBy])
        ]);
    }

    public function topEngagedEmployees(Request $request) {
        $validated = $request->validate([
            'start_at' => 'nullable|date|date_format:"Y-m-d"',
            'end_at' => 'nullable|date|date_format:"Y-m-d"',
            'employees' => 'nullable|array|exists:App\Models\User,id',
        ]);

        $reportsService = app(\App\Services\ReportsService::class);

        // if not start_at, end_at set than use start of month
        $start_at = !empty($validated['start_at']) ? Carbon::createFromFormat('Y-m-d',  $validated['start_at']) : now()->copy()->startOfMonth();
        $end_at = !empty($validated['end_at']) ? Carbon::createFromFormat('Y-m-d', $validated['end_at']) : now()->copy()->endOfMonth();
    
        $employee_ids = get_access_employees(\Auth::user(), $validated['employees'] ?? []);

        $users_analytics = $reportsService->getBurnoutAnalytics(
            \Auth::id(), $employee_ids->keys()->toArray(), $start_at, $end_at, 'user_id'
        );

        return response()->json([
            'data' => $users_analytics,
        ]);
    }

    /**
     * Get top analytics by field from analytics table
     * 
     */
    public function topByEmployees(Request $request) {
        $validated = $request->validate([
            'start_at' => 'nullable|date|date_format:"Y-m-d"',
            'end_at' => 'nullable|date|date_format:"Y-m-d"',
            'employees' => 'nullable|array|exists:App\Models\User,id',
            'key' => 'required'
        ]);

        $reportsService = app(\App\Services\ReportsService::class);

        // if not start_at, end_at set than use start of month
        $start_at = !empty($validated['start_at']) ? Carbon::createFromFormat('Y-m-d',  $validated['start_at']) : now()->copy()->startOfMonth();
        $end_at = !empty($validated['end_at']) ? Carbon::createFromFormat('Y-m-d', $validated['end_at']) : now()->copy()->endOfMonth();
    
        $employee_ids = get_access_employees(\Auth::user(), $validated['employees'] ?? []);

        $users_analytics = $reportsService->getQueryAnalytics(
            \Auth::id(), $employee_ids->keys()->toArray(), [$validated['key']], $start_at, $end_at, 'user_id',
        )->get()->sortByDesc($validated['key']);

        return response()->json([
            'data' => $users_analytics,
        ]);
    }

    public function attendance(Request $request) {
        $validated = $request->validate([
            'start_at' => 'nullable|date|date_format:"Y-m-d"',
            'employee_id' => 'required|exists:App\Models\User,id',
        ]);

        // if not start_at, end_at set than use start of month
        $start_at = !empty($validated['start_at']) ? Carbon::createFromFormat('Y-m-d',  $validated['start_at']) : now()->copy()->startOfWeek();
        $end_at = $start_at->copy()->addWeek();
        
        $employee_ids = get_access_employees(\Auth::user(), $validated['employees'] ?? []);

        // check wether have access to employee
        if(! in_array($validated['employee_id'], $employee_ids->keys()->toArray())) {
            return response()->json([
                'error' => 'Invalid employee'
            ], 403);
        }

        $reportsService = app(\App\Services\ReportsService::class);
        $data = $reportsService->getAttendanceAnalytics(
            \Auth::id(), [$validated['employee_id']], $start_at, $end_at, ['day', 'hour']
        );

        $dates = generate_date_range($start_at, $end_at, 'day', 'Y-m-d');

        return response()->json([
            'categories' => ['clock_in', 'clock_out', 'office_time', 'pauses_time', 'pauses'],
            'users' => $dates,
            'analytics' => $data
        ]);
    }
}
