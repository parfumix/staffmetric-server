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
            'groupBy' => 'nullable'
        ]);

        $reportsService = app(\App\Services\ReportsService::class);

        // if not start_at, end_at set than use start of month
        $start_at = !empty($validated['start_at']) ? Carbon::createFromFormat('Y-m-d',  $validated['start_at']) : now()->copy()->startOfMonth();
        $end_at = !empty($validated['end_at']) ? Carbon::createFromFormat('Y-m-d', $validated['end_at']) : now()->copy()->endOfMonth();
        
        $groupBy = isset($validated['groupBy']) ? $validated['groupBy'] : 'day';
        $dateRanges = generate_date_range($start_at, $end_at, $groupBy);

        //calculate prev time
        if( $groupBy == 'year' ) {
            $prev_start_at = $start_at->copy()->subYear();
            $prev_end_at = $end_at->copy()->subYear();
        } elseif ( $groupBy == 'month' ) {
            $prev_start_at = $start_at->copy()->subMonth();
            $prev_end_at = $end_at->copy()->subMonth();
        } elseif ( $groupBy == 'week' ) {
            $prev_start_at = $start_at->copy()->subWeek();
            $prev_end_at = $end_at->copy()->subWeek();
        } elseif ( $groupBy == 'day' ) {
            $prev_start_at = $start_at->copy()->subDay();
            $prev_end_at = $end_at->copy()->subDay();
        }

        //TODO check if manager through employeer get access to employees
        $access_to_employees = \Auth::user()->employees(\App\Models\User::ACCEPTED)->get()->reject(function ($u) use($validated) {
            return count($validated['employees'] ?? [])
                ? !in_array($u->id, $validated['employees'])
                : false;
        });
        $employee_ids = $access_to_employees->pluck('name', 'id');
        $employer = \Auth::user();

        $fields_to_select = [
            'email_secs' => ['title' => 'Email time', 'description' => 'Includes all emails websites.'],
            'social_network_secs' => ['title' => 'Social network time', 'description' => 'Includes all social websites.'],
            //'idle_secs' => ['title' => 'Pauses time', 'description' => 'Pauses time'],
            'productive_secs' => ['title' => 'Productive time', 'description' => 'Only Web/Apps marked as productive.'],
            'neutral_secs' => ['title' => 'Neutral time', 'description' => 'Only Web/Apps marked as neutral.'],
            'non_productive_secs' => ['title' => 'Non Productive Time', 'description' => 'Only Web/Apps marked as non-productive.'],
        ];

        $prev_period_data = $reportsService->getProductivityAnalytics(
            $employer->id, $employee_ids->keys()->toArray(), $prev_start_at, $prev_end_at, $groupBy, array_keys($fields_to_select)
        )->groupBy($groupBy);

        $current_period_data = $reportsService->getProductivityAnalytics(
            $employer->id, $employee_ids->keys()->toArray(), $start_at, $end_at, $groupBy, array_keys($fields_to_select)
        )->groupBy($groupBy);

        $formatted_current_data = collect($dateRanges)->map(function($date) use($current_period_data) {
            return $current_period_data->get($date) ? $current_period_data->get($date)->first() : [
                'email_secs' => 0,
                'social_network_secs' => 0,
                //'idle_secs' => 0,
                'productive_secs' => 0,
                'neutral_secs' => 0,
                'non_productive_secs' => 0,
            ];
        });

        $formatted_prev_data = collect($dateRanges)->map(function($date) use($prev_period_data) {
            return $prev_period_data->get($date) ? $prev_period_data->get($date)->first() : [
                'email_secs' => 0,
                'social_network_secs' => 0,
                //'idle_secs' => 0,
                'productive_secs' => 0,
                'neutral_secs' => 0,
                'non_productive_secs' => 0,
            ];
        });

        $metrics = [];
        foreach ($fields_to_select as $key => $item) {
            $metrics[$key]['title'] = $item['title'];    
            $metrics[$key]['description'] = $item['description'];    
            $metrics[$key]['current'] = $formatted_current_data->pluck($key)->sum();    
            $metrics[$key]['prev'] = $formatted_prev_data->pluck($key)->sum();    
        }

        return response()->json([
            'metrics' => $metrics,
            'categories' => $dateRanges,
            'prev_period_data' => [
                'productive_secs' => $formatted_prev_data->pluck('productive_secs')->map(function($el) { return intval($el); }),
                'neutral_secs' => $formatted_prev_data->pluck('neutral_secs')->map(function($el) { return intval($el); }),
                'non_productive_secs' => $formatted_prev_data->pluck('non_productive_secs')->map(function($el) { return intval($el); }),
            ],
            'current_period_data' => [
                'neutral_secs' => $formatted_current_data->pluck('neutral_secs')->map(function($el) { return intval($el); }),
                'productive_secs' => $formatted_current_data->pluck('productive_secs')->map(function($el) { return intval($el); }),
                'non_productive_secs' => $formatted_current_data->pluck('non_productive_secs')->map(function($el) { return 0 - intval($el); }),
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
        
        $groupBy = isset($validated['groupBy']) ? $validated['groupBy'] : 'day';

        //TODO check if manager through employeer get access to employees
        $access_to_employees = \Auth::user()->employees(\App\Models\User::ACCEPTED)->get()->reject(function ($u) use($validated) {
            return count($validated['employees'] ?? [])
                ? !in_array($u->id, $validated['employees'])
                : false;
        });
        $employee_ids = $access_to_employees->pluck('name', 'id');
        $employer = \Auth::user();

        $fields_to_select = ['productive_secs', 'neutral_secs', 'non_productive_secs'];

        $users_analytics = $reportsService->getProductivityAnalytics(
            $employer->id, $employee_ids->keys()->toArray(), $start_at, $end_at, [$groupBy, 'user_id'], $fields_to_select
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
        
        //TODO check if manager through employeer get access to employees
        $access_to_employees = \Auth::user()->employees(\App\Models\User::ACCEPTED)->get()->reject(function ($u) use($validated) {
            return count($validated['employees'] ?? [])
                ? !in_array($u->id, $validated['employees'])
                : false;
        });
        $employee_ids = $access_to_employees->pluck('name', 'id');

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
        
        //TODO check if manager through employeer get access to employees
        $access_to_employees = \Auth::user()->employees(\App\Models\User::ACCEPTED)->get()->reject(function ($u) use($validated) {
            return count($validated['employees'] ?? [])
                ? !in_array($u->id, $validated['employees'])
                : false;
        });
        $employee_ids = $access_to_employees->pluck('name', 'id');

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

        //TODO check if manager through employeer get access to employees
        $access_to_employees = \Auth::user()->employees(\App\Models\User::ACCEPTED)->get()->reject(function ($u) use($validated) {
            return count($validated['employees'] ?? [])
                ? !in_array($u->id, $validated['employees'])
                : false;
        });
        $employee_ids = $access_to_employees->pluck('name', 'id');
        $employer = \Auth::user();

        $data = $reportsService->getBurnoutAnalytics(
            $employer->id, $employee_ids->keys()->toArray(), $start_at, $end_at, $groupBy
        )->groupBy($groupBy);

        $formatted_data = collect($dateRanges)->map(function($date) use($data) {
            return $data->get($date) ? $data->get($date)->first() : [
                'burnout' => 0,
                'engagment' => 0,
            ];
        });

        return response()->json([
            'categories' => $dateRanges,
            'data' => [
                'burnout' => $formatted_data->pluck('burnout'),
                'engagment' => $formatted_data->pluck('engagment'),
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

        //TODO check if manager through employeer get access to employees
        $access_to_employees = \Auth::user()->employees(\App\Models\User::ACCEPTED)->get()->reject(function ($u) use($validated) {
            return count($validated['employees'] ?? [])
                ? !in_array($u->id, $validated['employees'])
                : false;
        });
        $employee_ids = $access_to_employees->pluck('name', 'id');
        $employer = \Auth::user();

        $users_analytics = $reportsService->getBurnoutAnalytics(
            $employer->id, $employee_ids->keys()->toArray(), $start_at, $end_at, [$groupBy, 'user_id']
        );

        return response()->json([
            'categories' => generate_date_range($start_at, $end_at, $groupBy),
            'users' => $employee_ids->keys()->reject(function($el) use($validated) { return isset($validated['employees']) && count($validated['employees']) ? !in_array($el, $validated['employees']) : false; }),
            'analytics' => $users_analytics->groupBy(['user_id', $groupBy])
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
        
        //TODO check if manager through employeer get access to employees
        $access_to_employees = \Auth::user()->employees(\App\Models\User::ACCEPTED)->get()->reject(function ($u) use($validated) {
            return count($validated['employees'] ?? [])
                ? !in_array($u->id, $validated['employees'])
                : false;
        });
        $employee_ids = $access_to_employees->pluck('name', 'id');

        // check wether have access to employee
        if(! in_array($validated['employee_id'], $employee_ids->keys()->toArray())) {
            return response()->json([
                'error' => 'Invalid employee'
            ], 403);
        }

        $employer = \Auth::user();

        $reportsService = app(\App\Services\ReportsService::class);
        $data = $reportsService->getAttendanceAnalytics(
            $employer->id, [$validated['employee_id']], $start_at, $end_at, ['day', 'hour']
        );

        $dates = generate_date_range($start_at, $end_at, 'day', 'Y-m-d');

        return response()->json([
            'categories' => ['clock_in', 'clock_out', 'office_time', 'pauses_time', 'pauses'],
            'users' => $dates,
            'analytics' => $data
        ]);
    }
}
