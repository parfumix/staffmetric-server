<?php

use Carbon\Carbon;

if (!function_exists('get_name_from_email')) {
    function get_name_from_email($email_address, $split = '@') {
        return ucwords(strtolower(substr($email_address, 0, strripos($email_address, $split))));
    }
}

if (!function_exists('get_domain_from_email')) {
    function get_domain_from_email($email) {
        $email_domain = explode('@', $email);
        $email_domain = $email_domain[1];

        return $email_domain;
    }
}

if (!function_exists('get_dates_by_interval')) {

    function get_dates_by_interval($interval = 'daily', $start_date) {
        if ($interval == 'weekly') {
            $start_of_date = $start_date->startOfWeek();
            $end_of_date = $start_of_date->copy()->endOfWeek();
        } elseif ($interval == 'monthly') {
            $start_of_date = $start_date->startOfMonth();
            $end_of_date = $start_of_date->copy()->endOfMonth();
        } elseif ($interval == 'yearly') {
            $start_of_date = $start_date->startOfYear();
            $end_of_date = $start_of_date->copy()->endOfYear();
        } else {
            $start_of_date = $start_date;
            $end_of_date = $start_of_date;
        }

        return [$start_of_date, $end_of_date];
    }
}

if (!function_exists('generate_date_range')) {
    function generate_date_range(Carbon $start_date, Carbon $end_date, $group = 'day', $formatter = null) {
        $dates = [];

        if (!$formatter) {
            if ($group == 'day') {
                $formatter = 'Y-m-d';
            } elseif ($group == 'week') {
                $formatter = 'Y-W';
            } elseif ($group == 'month') {
                $formatter = 'Y-m';
            } else {
                $formatter = 'Y';
            }
        }

        for ($date = $start_date->copy(); $date->lte($end_date); $date->addDay()) {
            if (!in_array($date->format($formatter), $dates)) {
                $dates[] = $date->format($formatter);
            }
        }

        return $dates;
    }
}

if (!function_exists('hours_range')) {
    function hours_range($from = null, $to = null, $format = 'H a') {
        $dt = Carbon::createFromTime(0);
        $dt2 = $dt->copy()->addDay(1);

        $hours = [];
        $dt->diffInHoursFiltered(function ($date) use (&$hours, $format, $from, $to) {
            if (!is_null($from)) {
                if ($date->format('H') < $from) {
                    return;
                }

            }

            if (!is_null($to)) {
                if ($date->format('H') > $to) {
                    return;
                }

            }

            $hours[$date->format('G')] = $date->format($format);
        }, $dt2);

        return $hours;
    }
}

if (!function_exists('generate_prev_date')) {
    function generate_prev_date($start_at, $end_at, $groupBy) {
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
        } elseif ( $groupBy == 'hour' ) {
            $prev_start_at = $start_at->copy()->subDay()->startOfDay();
            $prev_end_at = $end_at->copy()->subDay()->endOfDay();
        }

        return [$prev_start_at, $prev_end_at];
    }
}

if (!function_exists('get_access_employees')) {
    function get_access_employees($employer, $filterEmplyoees = []) {
        $access_to_employees = $employer->employees(\App\Models\User::ACCEPTED)->get()->reject(function ($u) use($filterEmplyoees) {
            return count($validated['employees'] ?? [])
                ? !in_array($u->id, $filterEmplyoees['employees'])
                : false;
        });
        
        return $access_to_employees->pluck('name', 'id');
    }
}
