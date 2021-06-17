<?php

namespace App\Services;

use Carbon\Carbon;
use \App\Models\Category;

class ReportsService {

    //todo adding cache for past days
    //todo when user change app category flush all cache.
    //todo or when category change productivity

    protected function categorize($query, $employer_id = null, $drop_deleted = true) {
        $query->addSelect([
            'my_apps.id as my_app_id',
            'my_apps.deleted_at as deleted_at',
            'users.name as user_name',
            'users.id as user_id',
            \DB::raw('dayname(activities.start_at) as dayname'),
            \DB::raw('hour(activities.start_at) as hour'),
            \DB::raw('month(activities.start_at) as month'),
            \DB::raw('week(activities.start_at) as week'),
            \DB::raw('date_format(activities.start_at, "%Y-%m-%d") as date'),
            \DB::raw("sum(activities.duration) as duration"),
        ]);

        $query->join('users', 'users.id', '=', 'activities.user_id');

        if(! is_null($employer_id)) {
            $query->leftJoin('employee_apps', function ($join) use($employer_id) {
                $join->on('employee_apps.name', '=', 'activities.app')
                    ->on('employee_apps.user_id', '=', \DB::raw($employer_id))
                    ->on(function ($query) {
                        return $query->where('employee_apps.employee_id', '=', \DB::raw('activities.user_id'))
                            ->orWhereNull('employee_apps.employee_id');
                    });
            });

            $query->leftJoin('productivities as p3', function ($join) {
                $join->on('p3.productivity_id', '=', 'users.id')
                    ->where('p3.productivity_type', '=', 'employees')
                    ->on('p3.category_id', '=', 'employee_apps.category_id');
            });

            $query->leftJoin('categories as c3', function ($join) {
                $join->on('c3.id', '=', 'employee_apps.category_id')
                    ->where(function ($query) {
                        $query->on('c3.user_id', 'users.id')
                            ->orWhereNull('c3.user_id');
                    });
            });

            $query->addSelect([
                \DB::raw('IFNULL(c3.id, IFNULL(c.id, IFNULL(c2.id, null))) as category_id'),
                \DB::raw('IFNULL(c3.title, IFNULL(c.title, IFNULL(c2.title, "Uncategorized"))) as title'),
                \DB::raw('IFNULL(p3.productivity, IFNULL(p.productivity, IFNULL(p2.productivity, IFNULL(c3.productivity, IFNULL(c.productivity, IFNULL(c2.productivity, "neutral")))))) as productivity'),
            ]);
        } else {
            $query->addSelect([
                \DB::raw('IFNULL(c.id, IFNULL(c2.id, null)) as category_id'),
                \DB::raw('IFNULL(c.title, IFNULL(c2.title, "Uncategorized")) as title'),
                \DB::raw('IFNULL(p.productivity, IFNULL(p2.productivity, IFNULL(c.productivity, IFNULL(c2.productivity, "neutral")))) as productivity'),
            ]);
        }

        $query->leftJoin('my_apps', function ($join) {
            $join->on('my_apps.name', '=', 'activities.app')
                ->on('my_apps.user_id', '=', 'activities.user_id');
        });

        $query->leftJoin('productivities as p', function ($join) {
            $join->on('p.productivity_id', '=', 'users.id')
                ->where('p.productivity_type', '=', 'user')
                ->on('p.category_id', '=', 'my_apps.category_id');
        });

        $query->leftJoin('categories as c', function ($join) {
            $join->on('c.id', '=', 'my_apps.category_id')
                ->where(function ($query) {
                    $query->on('c.user_id', 'users.id')
                        ->orWhereNull('c.user_id');
                });
        });


        $query->leftJoin('apps', function ($join) use($employer_id) {
            $join->on('apps.name', '=', 'activities.app')
                ->on(function ($query) {
                    return $query->where('apps.profile_id', '=', \DB::raw('users.profile_id'))
                        ->orWhereNull('apps.profile_id');
                });

            if(! is_null($employer_id)) {
                $join->on(function ($query) use($employer_id) {
                        return $query->where('apps.user_id', '=', \DB::raw($employer_id))
                            ->orWhereNull('apps.user_id');
                    });
            }
        });

        $query->leftJoin('productivities as p2', function ($join) {
            $join->on('p2.productivity_id', '=', 'users.profile_id')
                ->where('p2.productivity_type', '=', 'profile')
                ->on('p2.category_id', '=', 'apps.category_id');
        });

        $query->leftJoin('categories as c2', function ($join) {
            $join->on('c2.id', '=', 'apps.category_id')
                ->where(function ($query) {
                    $query->on('c2.user_id', 'users.id')
                        ->orWhereNull('c2.user_id');
                });
        });

        #@todo by default rm trashed results ..
        if( $drop_deleted ) {
            $query->whereNull('my_apps.deleted_at');
        }

        return $query;
    }


    public function getCategorizedSql(\App\Models\User $user) {
        $query = $user->activities();

        return $this->categorize($query);
    }


    //-----------------------------------------------------
    // Apps
    //-----------------------------------------------------

    public function getReportsAppsGroupedBy( $groupBy, $employee_id = null, $employer_id = null, Carbon $date = null, $device = null, $perPage = null ) {
        if( $groupBy == 'daily' ) {
            $items = $this->getDailyReportsByApps($employee_id, $employer_id, $date, $device, $perPage)->groupBy('hour');
        } elseif ( $groupBy == 'weekly' ) {
            $items = $this->getWeeklyReportsByApps($employee_id, $employer_id, $date, $device, $perPage)->groupBy('date');
        } elseif( $groupBy == 'monthly' ) {
            $items = $this->getMonthlyReportsByApps($employee_id, $employer_id, $date, $device, $perPage)->groupBy('week');
        } elseif( $groupBy == 'yearly' ) {
            $items = $this->getYearlyReportsByApps($employee_id, $employer_id, $date, $device, $perPage)->groupBy('month');
        } else {
            $items = $this->getReportsByApps($employee_id, $employer_id, $date, $device, $perPage);
        }

        return $items;
    }

    public function getEmployeesReportsByApps($employer_id, array $employees, Carbon $date = null, Carbon $end_at = null) {
        $query = \App\Models\Activity::with(['device', 'user'])
            ->whereIn('activities.user_id', $employees);

        $query->addSelect(['app', 'full_url']);

        $query = $this->categorize($query, $employer_id);

        if(! is_null($date)) {
            $query->whereDate('activities.start_at', '>=', $date->format('Y-m-d'));

            if( is_null($end_at) ) {
                $query->whereDate('activities.end_at', '<=', $date->format('Y-m-d'));
            }
        }

        if( $end_at ) {
            $query->whereDate('activities.end_at', '<=', $end_at->format('Y-m-d'));
        }

        $query->groupBy(['app', 'activities.user_id'])
            ->orderBy('duration', 'desc');

        return $query->get();
    }

    public function getReportsByApps( $employee_id, $employer_id = null, Carbon $date = null, \App\Models\Device $device = null, $perPage = null ) {
        $query = $device
            ? $device->activities()
            : \App\Models\User::findOrFail($employee_id)->activities();

        $query->addSelect(['app', 'full_url']);

        $query = $this->categorize($query, $employer_id);

        if(! is_null($date)) {
            $query->whereDate('activities.start_at', '>=', $date->format('Y-m-d'))
                ->whereDate('activities.end_at', '<=', $date->format('Y-m-d'));
        }

        $query->groupBy('app')
            ->orderBy('duration', 'desc');

        return $perPage
            ? $query->paginate($perPage)
            : $query->get();
    }

    public function getDailyReportsByApps( $employee_id, $employer_id = null, Carbon $date, $device = null, $perPage = null ) {
        $query = $device
            ? $device->activities()
            : \App\Models\User::findOrFail($employee_id)->activities();

        $query->addSelect(['app', 'full_url']);

        $query = $this->categorize($query, $employer_id);

        $query->whereDate('activities.start_at', '>=', $date->format('Y-m-d'))
            ->whereDate('activities.end_at', '<=', $date->format('Y-m-d'))
            ->groupBy([\DB::raw('hour(activities.start_at)'), 'app'])
            ->orderBy('hour', 'asc');

        return $perPage
            ? $query->paginate($perPage)
            : $query->get();
    }

    public function getWeeklyReportsByApps( $employee_id, $employer_id = null, Carbon $date, $device = null, $perPage = null ) {
        $query = $device
            ? $device->activities()
            : \App\Models\User::findOrFail($employee_id)->activities();

        $query->addSelect(['app', 'full_url']);

        $query = $this->categorize($query, $employer_id);

        $start_of_week_date = $date->copy()->startOfWeek();
        $end_of_week_date = $date->copy()->endOfWeek();

        $query->whereDate('activities.start_at', '>=', $start_of_week_date->format('Y-m-d'))
            ->whereDate('activities.end_at', '<=', $end_of_week_date->format('Y-m-d'));

        $query->groupBy(['date', 'app'])
            ->orderBy('date', 'asc');

        return $perPage
            ? $query->paginate($perPage)
            : $query->get();
    }

    public function getMonthlyReportsByApps( $employee_id, $employer_id = null, Carbon $date, $device = null, $perPage = null ) {
        $query = $device
            ? $device->activities()
            : \App\Models\User::findOrFail($employee_id)->activities();

        $query->addSelect(['app', 'full_url']);

        $query = $this->categorize($query, $employer_id);

        $start_of_month_date = $date->copy()->startOfMonth();
        $end_of_month_date = $date->copy()->endOfMonth();

        $query->whereDate('activities.start_at', '>=', $start_of_month_date->format('Y-m-d'))
            ->whereDate('activities.end_at', '<=', $end_of_month_date->format('Y-m-d'));

        $query->groupBy(['week', 'app'])
            ->orderBy('week', 'asc');

        return $perPage
            ? $query->paginate($perPage)
            : $query->get();
    }

    public function getYearlyReportsByApps( $employee_id, $employer_id, Carbon $date, $device = null, $perPage = null ) {
        $query = $device
            ? $device->activities()
            : \App\Models\User::findOrFail($employee_id)->activities();

        $query->addSelect(['app', 'full_url']);

        $query = $this->categorize($query, $employer_id);

        $start_of_year_date = $date->copy()->startOfYear();
        $end_of_year_date = $date->copy()->endOfYear();

        $query->whereDate('activities.start_at', '>=', $start_of_year_date->format('Y-m-d'))
            ->whereDate('activities.end_at', '<=', $end_of_year_date->format('Y-m-d'));

        $query->groupBy(['month', 'app'])
            ->orderBy('month', 'asc');

        return $perPage
            ? $query->paginate($perPage)
            : $query->get();
    }



    //-----------------------------------------------------
    // Categories
    //-----------------------------------------------------

    public function getReportsCategoriesGroupedBy( $groupBy, $employee_id, $employer_id = null, Carbon $date = null, $device = null, $perPage = null ) {
        if( $groupBy == 'daily' ) {
            $items = $this->getDailyReportsByCategories($employee_id, $employer_id, $date, $device, $perPage)->groupBy('hour');
        } elseif ( $groupBy == 'weekly' ) {
            $items = $this->getWeeklyReportsByCategories($employee_id, $employer_id, $date, $device, $perPage)->groupBy('date');
        } elseif( $groupBy == 'monthly' ) {
            $items = $this->getMonthlyReportsByCategories($employee_id, $employer_id, $date, $device, $perPage)->groupBy('week');
        } elseif( $groupBy == 'yearly' ) {
            $items = $this->getYearlyReportsByCategories($employee_id, $employer_id, $date, $device, $perPage)->groupBy('month');
        } else {
            $items = $this->getReportsByCategories($employee_id, $employer_id, $date, $device, $perPage);
        }

        return $items;
    }

    public function getEmployeesReportsByCategories($employer_id, array $employees, Carbon $date = null) {
        $general_categories = \App\Models\Category::whereNull('user_id')->get();

        $query = \App\Models\Activity::with(['device', 'user'])
            ->whereIn('activities.user_id', $employees);

        $query = $this->categorize($query, $employer_id);

        if(! is_null($date)) {
            $query->whereDate('activities.start_at', '>=', $date->format('Y-m-d'))
                ->whereDate('activities.end_at', '<=', $date->format('Y-m-d'));
        }

        $query->groupBy(['category_id', 'activities.user_id']);

        if( $general_categories->count() ) {
            $query->havingRaw('category_id in (' . $general_categories->pluck('id')->implode(',') . ')');
        }

        return $query->get();
    }

    public function getReportsByCategories( $employee_id, $employer_id = null, Carbon $date = null, $device = null, $perPage = null ) {
        $query = $device
            ? $device->activities()
            : \App\Models\User::findOrFail($employee_id)->activities();

        $query = $this->categorize($query, $employer_id);

        if(! is_null($date)) {
            $query->whereDate('activities.start_at', '>=', $date->format('Y-m-d'))
                ->whereDate('activities.end_at', '<=', $date->format('Y-m-d'));
        }

        $query->groupBy(['category_id']);

        return $perPage
            ? $query->paginate($perPage)
            : $query->get();
    }

    public function getDailyReportsByCategories( $employee_id, $employer_id = null, Carbon $date, $device = null, $perPage = null ) {
        $query = $device
            ? $device->activities()
            : \App\Models\User::findOrFail($employee_id)->activities();

        $query = $this->categorize($query, $employer_id);

        $query->whereDate('activities.start_at', '>=', $date->format('Y-m-d'))
            ->whereDate('activities.end_at', '<=', $date->format('Y-m-d'))
            ->groupBy(['hour', 'category_id'])
            ->orderBy('hour', 'asc');

        return $perPage
            ? $query->paginate($perPage)
            : $query->get();
    }

    public function getWeeklyReportsByCategories( $employee_id, $employer_id = null, Carbon $date, $device = null, $perPage = null ) {
        $query = $device
            ? $device->activities()
            : \App\Models\User::findOrFail($employee_id)->activities();

        $query = $this->categorize($query, $employer_id);

        $start_of_week_date = $date->copy()->startOfWeek();
        $end_of_week_date = $date->copy()->endOfWeek();

        $query->whereDate('activities.start_at', '>=', $start_of_week_date->format('Y-m-d'))
            ->whereDate('activities.end_at', '<=', $end_of_week_date->format('Y-m-d'));

        $query->groupBy(['date', 'category_id'])
            ->orderBy('date', 'asc');

        return $perPage
            ? $query->paginate($perPage)
            : $query->get();
    }

    public function getMonthlyReportsByCategories( $employee_id, $employer_id = null, Carbon $date, $device = null, $perPage = null ) {
        $query = $device
            ? $device->activities()
            : \App\Models\User::findOrFail($employee_id)->activities();

        $query = $this->categorize($query, $employer_id);

        $start_of_month_date = $date->copy()->startOfMonth();
        $end_of_month_date = $date->copy()->endOfMonth();

        $query->whereDate('activities.start_at', '>=', $start_of_month_date->format('Y-m-d'))
            ->whereDate('activities.end_at', '<=', $end_of_month_date->format('Y-m-d'));

        $query->groupBy(['week', 'category_id'])
            ->orderBy('week', 'asc');

        return $perPage
            ? $query->paginate($perPage)
            : $query->get();
    }

    public function getYearlyReportsByCategories( $employee_id, $employer_id = null, Carbon $date, $device = null, $perPage = null ) {
        $query = $device
            ? $device->activities()
            : \App\Models\User::findOrFail($employee_id)->activities();

        $query = $this->categorize($query, $employer_id);

        $start_of_year_date = $date->copy()->startOfYear();
        $end_of_year_date = $date->copy()->endOfYear();

        $query->whereDate('activities.start_at', '>=', $start_of_year_date->format('Y-m-d'))
            ->whereDate('activities.end_at', '<=', $end_of_year_date->format('Y-m-d'));

        $query->groupBy(['month', 'category_id'])
            ->orderBy('month', 'asc');

        return $perPage
            ? $query->paginate($perPage)
            : $query->get();
    }




    //-----------------------------------------------------
    // Productivity
    //-----------------------------------------------------

    public function getReportsProductivityGroupedBy( $groupBy, $employee_id, $employer_id = null, Carbon $date, $device = null, $perPage = null ) {
        if( $groupBy == 'daily' ) {
            $data = $this->getDailyProductivity($employee_id, $employer_id, $date, $device, $perPage)->groupBy('hour');
        } elseif ( $groupBy == 'weekly' ) {
            $data = $this->getWeeklyProductivity($employee_id, $employer_id, $date, $device, $perPage )->groupBy('date');
        } elseif( $groupBy == 'monthly' ) {
            $data = $this->getMonthlyProductivity($employee_id, $employer_id, $date, $device, $perPage)->groupBy('week');
        } elseif( $groupBy == 'yearly' ) {
            $data = $this->getYearlyProductivity($employee_id, $employer_id, $date, $device, $perPage)->groupBy('month');
        } else {
            $data = $this->getByProductivity($employee_id, $employer_id, $date, $device, $perPage);
        }

        return $data;
    }

    public function getEmployeesReportsByProductivity($employer_id, array $employees, Carbon $date = null, Carbon $end_date = null) {
        $query = \App\Models\Activity::with(['device', 'user'])
            ->whereIn('activities.user_id', $employees);

        $query = $this->categorize($query, $employer_id);

        if(! is_null($date)) {
            $query->whereDate('activities.start_at', '>=', $date->format('Y-m-d'));

            if( is_null($end_date) ) {
                $query->whereDate('activities.end_at', '<=', $date->format('Y-m-d'));
            }
        }

        if(! is_null($end_date)) {
            $query->whereDate('activities.end_at', '<=', $end_date->format('Y-m-d'));
        }

        $query->groupBy(['productivity', 'activities.user_id'])
            ->orderBy('duration', 'asc');

        return $query->get();
    }


    public function getEmployeesProductiveReports($employer_id, array $employees, Carbon $date = null) {
        $query = \App\Models\Activity::with(['device', 'user'])
            ->whereIn('activities.user_id', $employees);

        $query = $this->categorize($query, $employer_id);

        if(! is_null($date)) {
            $query->whereDate('activities.start_at', '>=', $date->format('Y-m-d'))
                ->whereDate('activities.end_at', '<=', $date->format('Y-m-d'));
        }

        $query->having('productivity', 'productive');

        $query->groupBy(['month', 'productivity', 'activities.user_id'])
            ->orderBy('duration', 'asc');

        return $query->get();
    }

    public function getEmployeesDailyProductivityReports($employer_id, array $employees, Carbon $from = null, Carbon $to = null) {
        $query = \App\Models\Activity::with(['device', 'user'])
            ->whereIn('activities.user_id', $employees);

        $query = $this->categorize($query, $employer_id);

        if(! $from)
            $from = now()->subWeek(1);

        if(! is_null($from))
            $query->whereDate('activities.start_at', '>=', $from->format('Y-m-d'));

        if(! is_null($to))
            $query->whereDate('activities.start_at', '<=', $to->format('Y-m-d'));

        $query->groupBy(['date', 'productivity', 'activities.user_id'])
            ->orderBy('duration', 'asc');

        return $query->get();
    }

    public function getByProductivity($employee_id, $employer_id = null, Carbon $date = null, $device = null, $perPage = null, $last_activity_index = null) {
        $query = $device
            ? $device->activities()
            : \App\Models\User::findOrFail($employee_id)->activities();

        $query->addSelect(['activities.project_id']);

        if( $last_activity_index ) {
            $query->where('activities.id', '>', $last_activity_index);
        }

        $query = $this->categorize($query, $employer_id);

        if(! is_null($date)) {
            $query->whereDate('activities.start_at', '>=', $date->format('Y-m-d'))
                ->whereDate('activities.end_at', '<=', $date->format('Y-m-d'));
        }

        $query->groupBy(['activities.project_id', 'productivity']);

        return $perPage
            ? $query->paginate($perPage)
            : $query->get();
    }

    public function getDailyProductivity($employee_id, $employer_id = null, Carbon $date, $device = null, $perPage = null ) {
        $query = $device
            ? $device->activities()
            : \App\Models\User::findOrFail($employee_id)->activities();

        $query = $this->categorize($query, $employer_id);

        $query->whereDate('activities.start_at', '>=', $date->format('Y-m-d'))
            ->whereDate('activities.end_at', '<=', $date->format('Y-m-d'));

        $query->groupBy(['hour', 'productivity'])
            ->orderBy('hour', 'asc');

        return $perPage
            ? $query->paginate($perPage)
            : $query->get();
    }

    public function getWeeklyProductivity($employee_id, $employer_id = null, Carbon $date, $device = null, $perPage = null ) {
        $query = $device
            ? $device->activities()
            : \App\Models\User::findOrFail($employee_id)->activities();

        $query = $this->categorize($query, $employer_id);

        $start_of_week_date = $date->copy()->startOfWeek();
        $end_of_week_date = $date->copy()->endOfWeek();

        $query->whereDate('activities.start_at', '>=', $start_of_week_date->format('Y-m-d'))
            ->whereDate('activities.end_at', '<=', $end_of_week_date->format('Y-m-d'));

        $query->groupBy(['date', 'productivity'])
            ->orderBy('date', 'asc');

        return $perPage
            ? $query->paginate($perPage)
            : $query->get();
    }

    public function getMonthlyProductivity($employee_id, $employer_id = null, Carbon $date, $device = null, $perPage = null ) {
        $query = $device
            ? $device->activities()
            : \App\Models\User::findOrFail($employee_id)->activities();

        $query = $this->categorize($query, $employer_id);

        $start_of_month_date = $date->copy()->startOfMonth();
        $end_of_month_date = $date->copy()->endOfMonth();

        $query->whereDate('activities.start_at', '>=', $start_of_month_date->format('Y-m-d'))
            ->whereDate('activities.end_at', '<=', $end_of_month_date->format('Y-m-d'));

        $query->groupBy(['week', 'productivity'])
            ->orderBy('week', 'asc');

        return $perPage
            ? $query->paginate($perPage)
            : $query->get();
    }

    public function getYearlyProductivity($employee_id, $employer_id = null, Carbon $date, $device = null, $perPage = null ) {
        $query = $device
            ? $device->activities()
            : \App\Models\User::findOrFail($employee_id)->activities();

        $query = $this->categorize($query, $employer_id);

        $start_of_year_date = $date->copy()->startOfYear();
        $end_of_year_date = $date->copy()->endOfYear();

        $query->whereDate('activities.start_at', '>=', $start_of_year_date->format('Y-m-d'))
            ->whereDate('activities.end_at', '<=', $end_of_year_date->format('Y-m-d'));

        $query->groupBy(['month', 'productivity'])
            ->orderBy('month', 'asc');

        return $perPage
            ? $query->paginate($perPage)
            : $query->get();
    }


    //-----------------------------------------------------
    // Analytics
    //-----------------------------------------------------

    public function getQueryAnalytics($employer_id, array $employees = [], array $columns = [], Carbon $start, Carbon $end, $groupBy = 'day') {
        $query = \DB::table('analytics')
            ->where('analytics.employer_id', $employer_id);

        $query->select([
            \DB::raw("user_id"),
            \DB::raw("DATE_FORMAT(analytics.employee_time, '%H') as hour"),
            \DB::raw("DATE_FORMAT(analytics.employee_time, '%Y-%m-%d') as day"),
            \DB::raw("DATE_FORMAT(analytics.employee_time, '%Y/%V') as week"),
            \DB::raw("DATE_FORMAT(analytics.employee_time, '%Y-%m') as month"),
            \DB::raw("DATE_FORMAT(analytics.employee_time, '%Y') as year"),
        ]);

        $columns = (array)$columns;
        foreach ($columns as $column) {
            $query->addSelect(\DB::raw("IFNULL(SUM(analytics.{$column}), 0) as {$column}"));
        }

        if(count($employees)) {
            $query->whereIn('analytics.user_id', $employees)
                ->leftJoin('users', 'users.id', '=', 'analytics.user_id')
                ->addSelect([\DB::raw('users.name as name')]);
        }

        if(! is_null($start)) {
            $query->whereDate('analytics.employee_time', '>=', $start->format('Y-m-d'));
        }

        if(! is_null($end)) {
            $query->whereDate('analytics.employee_time', '<=', $end->format('Y-m-d'));
        }

        $query->groupBy($groupBy)
            ->orderBy($groupBy);

        return $query;
    }

    public function getProductivityAnalytics($employer_id, array $employees = [], Carbon $start, Carbon $end, $groupBy = 'month') {
        return $this->getQueryAnalytics($employer_id, $employees, ['productive_secs', 'neutral_secs', 'non_productive_secs'], $start, $end, $groupBy)->get();
    }

    public function getEmailAnalytics($employer_id, array $employees = [], Carbon $start, Carbon $end, $groupBy = 'hour') {
        return $this->getQueryAnalytics($employer_id, $employees, ['email_secs'], $start, $end, $groupBy)->get();
    }



    //-----------------------------------------------------
    // Top Apps
    //-----------------------------------------------------

    public function getTopAnalytics(array $employees = [], Carbon $start = null, Carbon $end = null, Category $category = null) {
        $query = \App\Models\TopApp::whereIn('top_apps.user_id', $employees)
            ->addSelect(['app', \DB::raw("sum(duration) as duration")])
            ->leftJoin('apps', 'apps.name', '=', 'top_apps.app');

        if($category) {
            $query->where('top_apps.category_id', $category->id);
        }

        if( $start ) {
            $query->whereDate('top_apps.created_at', '>=', $start->format('Y-m-d'));
        }

        if( $end ) {
            $query->whereDate('top_apps.created_at', '<=', $end->format('Y-m-d'));
        }
        
        $query->groupBy(['app'])
            ->orderBy('top_apps.duration', 'desc');

        return $query->get();
    }

    public function getAppsByUser(\App\Models\User $user, $group_by_category = true) {
        $apps_providers = \App\Models\App::where(function ($query) use($user) {
            return $query->where('apps.user_id', $user->id)
                ->orWhere('apps.user_id', null);
        })->leftJoin('deleted_apps', function ($join) use($user) {
            $join->on('deleted_apps.user_id', '=', \DB::raw($user->id))
                ->on('deleted_apps.name', 'apps.name');
        })->whereNull('deleted_apps.id')
            ->addSelect([\DB::raw('apps.*')])
            ->get();

        if($group_by_category) {
            return $apps_providers->groupBy('category_id');
        }

        return $apps_providers;
    }
}
