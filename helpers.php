<?php

use Carbon\Carbon;

if(! function_exists('get_name_from_email')) {
    function get_name_from_email($email_address, $split='@') {
        return ucwords(strtolower(substr($email_address, 0, strripos($email_address, $split))));
    }
}

if(! function_exists('get_domain_from_email')) {
    function get_domain_from_email($email) {
        $email_domain = explode('@', $email);
        $email_domain = $email_domain[1];

        return $email_domain;
    }
}

if(! function_exists('get_dates_by_interval')) {

    function get_dates_by_interval($interval = 'daily', $start_date) {
        if ($interval == 'weekly') {
            $start_of_date = $start_date->startOfWeek();
            $end_of_date = $start_of_date->copy()->endOfWeek();
        } elseif ($interval == 'monthly')  {
            $start_of_date = $start_date->startOfMonth();
            $end_of_date = $start_of_date->copy()->endOfMonth();
        } elseif( $interval == 'yearly' ) {
            $start_of_date = $start_date->startOfYear();
            $end_of_date = $start_of_date->copy()->endOfYear();
        } else {
            $start_of_date = $start_date;
            $end_of_date = $start_of_date;
        }

        return [$start_of_date, $end_of_date];
    }
}

if(! function_exists('generate_date_range')) {
    function generate_date_range(Carbon $start_date, Carbon $end_date, $group = 'day') {
        $dates = [];

        if($group == 'day') {
            $formatter = 'Y-m-d';
        } elseif ( $group == 'week' ) {
            $formatter = 'Y/W';
        } elseif ( $group == 'month' ) {
            $formatter = 'Y-m';
        } else {
            $formatter = 'Y';
        }

        for($date = $start_date->copy(); $date->lte($end_date); $date->addDay()) {
            if(! in_array($date->format($formatter), $dates)) {
                $dates[] = $date->format($formatter);
            }
        }

        return $dates;
    }
}