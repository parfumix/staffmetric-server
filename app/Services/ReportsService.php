<?php

namespace App\Services;

use Carbon\Carbon;
use \App\Models\Category;

class ReportsService {

    public function categorize($query, $employer_id = null, $drop_deleted = true) {
        $query->addSelect([
            // joined columns
            'my_apps.id as my_app_id',
            'my_apps.deleted_at as deleted_at',
            'users.name as user_name',
            'users.id as user_id',

            \DB::raw('hour(activities.start_at) as hour'),
            \DB::raw('dayname(activities.start_at) as dayname'),
            \DB::raw('week(activities.start_at) as week'),
            \DB::raw('month(activities.start_at) as month'),
            \DB::raw('date_format(activities.start_at, "%Y-%m-%d") as date'),

            \DB::raw("sum(activities.duration) as duration"),
        ]);

        $query->join('users', 'users.id', '=', 'activities.user_id');

        if(! is_null($employer_id)) {
            // FOR EMPLOYER APPS
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

        // FOR MY APPS
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

        // FOR GLOBAL APPS
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


    //-----------------------------------------------------
    // CONSOLE
    //-----------------------------------------------------

    public function getUserApps($employee_id, $apps_to_exclude = [], Carbon $date = null, $limit = null) {
        $query = \App\Models\User::findOrFail($employee_id)->activities();

        $query->addSelect([
            \DB::raw("activities.app as app"),
            \DB::raw("sum(activities.duration) as duration"),
        ]);
       
        // exclude idle time
        $query->whereNotNull('app');

        $query->whereNotIn('app', $apps_to_exclude);

        if(! is_null($date)) {
            $query->whereDate('activities.start_at', '>=', $date->format('Y-m-d'))
                ->whereDate('activities.end_at', '<=', $date->format('Y-m-d'));
        }

        $query->orderBy('duration', 'desc');

        $query->groupBy(['app']);

        if($limit) {
            $query->limit($limit);
        }

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

        // exclude idle time
        $query->whereNotNull('app');

        // detect productivity app
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

    public function getIdle($employee_id, Carbon $date = null, Carbon $end = null, $last_activity_index = null) {
        $query = \App\Models\User::findOrFail($employee_id)->activities();

        $query->addSelect([
            \DB::raw("activities.project_id as project_id"),
            \DB::raw("sum(activities.duration) as duration"),
        ]);

        if ($last_activity_index) {
            $query->where('activities.id', '>', $last_activity_index);
        }

        if (!is_null($date)) {
            $query->whereDate('activities.start_at', '>=', $date->format('Y-m-d'));

            if (is_null($end)) {
                $query->whereDate('activities.end_at', '<=', $date->format('Y-m-d'));
            }
        }

        if (!is_null($end)) {
            $query->whereDate('activities.end_at', '<=', $end->format('Y-m-d'));
        }

        $query->whereNull('activities.app');

        $query->groupBy(['activities.project_id', 'activities.id']);

        return $query->get();
    }

    public function getEmployeeAppTotalTime($employee_id, Carbon $date = null, Carbon $end = null, $only_urls = null, $providers = [], $last_activity_index = null) {
        $query = \App\Models\User::findOrFail($employee_id)->activities();

        $query->addSelect([
            \DB::raw("activities.project_id as project_id"),
            \DB::raw("sum(activities.duration) as duration"),
        ]);

        if ($last_activity_index) {
            $query->where('activities.id', '>', $last_activity_index);
        }

        if (!is_null($date)) {
            $query->whereDate('activities.start_at', '>=', $date->format('Y-m-d'));

            if (is_null($end)) {
                $query->whereDate('activities.end_at', '<=', $date->format('Y-m-d'));
            }
        }

        if (!is_null($end)) {
            $query->whereDate('activities.end_at', '<=', $end->format('Y-m-d'));
        }

        $query->whereNotNull('activities.app');

        if(! is_null($only_urls)) {
            $query->where('activities.is_url', $only_urls);
        }

        $query->where(function ($query) use ($providers) {
            foreach ($providers as $provider) {
                $query->orWhere('app', 'like', '%' . $provider . '%');
            }
        });

        $query->groupBy(['activities.project_id']);

        return $query->get();
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

    public function getQueryTopApps(array $employees = [], Carbon $start = null, Carbon $end = null, Category $category = null) {
        $query = \App\Models\TopApp::whereIn('top_apps.user_id', $employees)
            ->addSelect(['app', 'categories.name', \DB::raw("sum(duration) as duration")])
            ->leftJoin('categories', 'categories.id as category_id', 'categories.name as category_name', '=', 'top_apps.category_id');

        if($category) {
            $query->where('top_apps.category_id', $category->id);
        }

        if( $start ) {
            $query->whereDate('top_apps.created_at', '>=', $start->format('Y-m-d'));
        }

        if( $end ) {
            $query->whereDate('top_apps.created_at', '<=', $end->format('Y-m-d'));
        }
        
        return $query;
    }

    public function getTopApps(array $employees = [], Carbon $start = null, Carbon $end = null, Category $category = null) {
        return $this->getQueryTopApps($employees, $start, $end, $category)
            ->groupBy(['app'])
            ->orderBy('top_apps.duration', 'desc')
            ->get();
    }

    public function getTopCategories(array $employees = [], Carbon $start = null, Carbon $end = null, Category $category = null) {
        return $this->getQueryTopApps($employees, $start, $end, $category)
            ->groupBy(['category_id'])
            ->orderBy('top_apps.duration', 'desc')
            ->get();
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
