<?php

use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

//TODO adding func to adding apps to app by employeer, in order to set category for each app

Artisan::command('staffmetric:apps', function () {
    $this->line('---------------- started at ' . now()->format('Y-m-d H:i:s') . ' ----------------');

    $users = \App\Models\User::whereNotNull('email_verified_at')->get();
    $this->info('Users to be processed: ' . count($users));
    $this->info("");


    $this->info('');
    $this->info('Total users processed: ' .  count($users));
    $this->info("\n\n");
})->describe('Calculate apps for each user');

Artisan::command('staffmetric:top_apps', function () {
    $this->line('---------------- started at ' . now()->format('Y-m-d H:i:s') . ' ----------------');

    $users = \App\Models\User::whereNotNull('email_verified_at')->get();
    $this->info('Users to be processed: ' . count($users));
    $this->info("");

    $onlySpecificProviders = [];
    $data_to_limit_per_category = 7;

    foreach ($users as $user) {
        $employer = $user->employers()->first();

        $this->info(sprintf('Processing %s', $user->email));

        if(! $employer) {
            $this->info("Skip, not found any employers\n");
            continue;
        }

        if($employer->pivot->isDisabled()) {
            $this->info("Skip, user is not enabled for current employer\n");
            continue;
        }

        if(! $employer->pivot->isAccepted()) {
            $this->info("Skip, user not accepted invitation\n");
            continue;
        }

        $reportsService = app(\App\Services\ReportsService::class);

        // get current last_index
        $last_index = $user->topApps()
            ->whereNotNull('last_index')
            ->where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->first();

        $last_index_id = $last_index ? $last_index->last_index : null;

        $this->info(sprintf('Fetching activities for user "%s"', $user->name));

        $query = $user->activities()
            ->addSelect(['activities.id', 'app']);

        $query = $reportsService->categorize($query, $employer->id);
    
        // exclude null apps and duration 0
        $query->whereNotNull('app')
            ->where('duration', '>', 0);

        // select from sepcific index
        if($last_index_id) {
            $query->where('activities.id', '>', $last_index_id);
        }

        // if we want to calculate top apps for only specific providers
        if(count($onlySpecificProviders)) {
            $query->where(function ($query) use($onlySpecificProviders) {
                foreach ($onlySpecificProviders as $app) {
                    $query->orWhere('app', 'like', '%' . $app . '%');
                }
            });
        }

        // group by activities id
        $query->groupBy('activities.id')
            ->orderBy('activities.id', 'asc');

        $data = $query->get();

        // get last item in order to register last_index
        $last_item = $data->last();

        $handleInsert = function($categoryId, $appName, $apps, $last_index) use($user) {
            $user->topApps()->create([
                'project_id' => null, 
                'user_id' => $user->id, 
                'category_id' => $categoryId ? $categoryId : null, 
                'app' => $appName, 
                'duration' => $apps->sum('duration'), 
                'last_index' => $last_index
            ]);
        };

        $data->groupBy('category_id')->each(function($items, $groupByCategoryId) use($handleInsert, $data_to_limit_per_category, $last_item) {

            // filter apps that have url
            $groupped_apps = collect($items->sortBy('duration'))
            ->groupBy('app')->sortByDesc(function($values) {
                return $values->sum('duration');
            })->take($data_to_limit_per_category);


            $counter = 0;
            $groupped_apps->each(function ($apps, $appName) use($groupByCategoryId, $handleInsert, &$counter, $last_item, $groupped_apps) {
                $counter++;
                $handleInsert($groupByCategoryId, $appName, $apps, $counter == count($groupped_apps) ? $last_item->id : null);
            });
        });
        
        $this->info(sprintf(' ----- Data to insert %s', $data->count()));
    }

    $this->info('');
    $this->info('Total users processed: ' .  count($users));
    $this->info("\n\n");
})->describe('Calculate top apps for each user');

Artisan::command('staffmetric:uncategorized', function () {
    \App\Models\User::all()->map(function (\App\Models\User $user) {
        if( $user->canNotify( \App\Models\User::DB_NOTIFY) ) {
            if( ($topUncategorizedApps = $user->getUserUncategorizedApps(5)) && $topUncategorizedApps->count() ) {
                $user->notify( new \App\Notifications\UncategorizedApps($topUncategorizedApps) );
            }
        }
    });
})->describe('Check for uncategorized apps and sent notification');

if(! function_exists('calculate_analytics')) {
    function calculate_analytics($user, $employer, $context) {
        $user_timezone = config('app.timezone');
        $employer_local_time = now($user_timezone);

        $user_tracking_days = ['Mon', 'Tue'];
        $user_start_at = 9;
        $user_end_at = 18;

        $is_over_time = in_array($employer_local_time->format('l'), $user_tracking_days) &&
            (int)$employer_local_time->format('H') >= $user_end_at;

        $reportsService = app(\App\Services\ReportsService::class);

        // GET LAST INDEX FOR IDLE AND ACTIVITIES
        $last_user_analytic = $user->analytics()
            ->where('employer_id', $employer->id)
            ->orderBy('id', 'desc')
            ->first();

        $last_index_id = isset($last_user_analytic->last_index_id)
            ? $last_user_analytic->last_index_id
            : null;

        $apps_providers = $reportsService->getAppsByUser($employer);
        $email_category = \App\Models\Category::where('title', 'Email')->first();
        $social_category = \App\Models\Category::where('title', 'Social')->first();

        // FETCH USER APP TIME
        $user_app_time = $reportsService->getEmployeeAppTotalTime(
            $user->id, $employer_local_time, null, false, $last_index_id
        )->pluck('duration', 'project_id');

        // FETCH USER WEB TIME
        $user_web_time = $reportsService->getEmployeeAppTotalTime(
            $user->id, $employer_local_time, null, true, $last_index_id,
        )->pluck('duration', 'project_id');

        // FETCH PRODUCTIVITY TIME
        $user_productivity_reports = $reportsService->getByProductivity(
            $user->id, $employer->id, $employer_local_time, null, null, $last_index_id
        )->groupBy('project_id');

        // FETCH WEB MAIL TIME
        $user_web_mail_time = [];
        if( $email_category ) {
            $user_web_mail_time = $reportsService->getEmployeeProviderTotalTime(
                $user->id, $apps_providers[$email_category->id]->pluck('name'), $employer_local_time, null, $last_index_id
            )->pluck('duration', 'project_id');
        }
        
        // FETCH SOCIAL TIME
        $user_web_social_time = [];
        if( $social_category ) {
            $user_web_social_time = $reportsService->getEmployeeProviderTotalTime(
                $user->id, $apps_providers[$social_category->id]->pluck('name'), $employer_local_time, null, $last_index_id
            )->pluck('duration', 'project_id');
        }

        //INSERT ANALYTICS
        $data_to_insert = [];

        //collect mail and social time
        foreach ($user_web_social_time as $project_id => $duration) {
            $data_to_insert[$project_id]['social_network_secs'] = $duration;
        }

        foreach ($user_web_mail_time as $project_id => $duration) {
            $data_to_insert[$project_id]['email_secs'] = $duration;
        }

        //collect productivity data to array
        if( $user_productivity_reports->count() ) {
            //extract last index id
            $last_index_id = $user->activities()
                ->orderBy('id', 'desc')
                ->limit(1)
                ->firstOrFail();

            $last_index_id = $last_index_id->id;

            foreach ($user_productivity_reports as $project_id => $reports) {
                $productive_mins = $neutral_mins = $non_productive_mins = null;

                $idle_secs = null;
                $idle_count = null;

                foreach($reports as $report) {

                    // calculate idle time
                    if( empty($report['app']) ) {
                        $idle_secs += $report['duration'];
                        $idle_count++;
                    }

                    // calculate productivity
                    if($report['productivity'] == 'neutral') {
                        $neutral_mins += $report['duration'];
                    } elseif ($report['productivity'] == 'non-productive') {
                        $non_productive_mins += $report['duration'];
                    } elseif ($report['productivity'] == 'productive') {
                        $productive_mins += $report['duration'];
                    }
                }

                $over_time_secs = null;
                if($is_over_time) {
                    $over_time_secs = $productive_mins + $non_productive_mins + $neutral_mins;
                }

                $data_to_insert[$project_id]['neutral_secs'] = $neutral_mins;
                $data_to_insert[$project_id]['non_productive_secs'] = $non_productive_mins;
                $data_to_insert[$project_id]['productive_secs'] = $productive_mins;
                $data_to_insert[$project_id]['overtime_secs'] = $over_time_secs;
                $data_to_insert[$project_id]['total_secs'] = $productive_mins + $non_productive_mins + $neutral_mins;
                $data_to_insert[$project_id]['office_secs'] = $productive_mins + $non_productive_mins + $neutral_mins;
                $data_to_insert[$project_id]['app_usage'] = $user_app_time->get($project_id, null);
                $data_to_insert[$project_id]['web_usage'] = $user_web_time->get($project_id, null);
            }
        }

        if( count($data_to_insert) ) {
            foreach ($data_to_insert as $project_id => $data) {
                $analytic = $user->analytics()->create([
                    'project_id' => !empty($project_id) ? $project_id : null,
                    'employer_id' => $employer->id,
                    'last_index_id' => $last_index_id ?? null,
                    'employee_time' => $employer_local_time,
                ] + $data);

                $analytic->save();
                $context->info("");
                $context->info('Inserting data ' . json_encode($analytic->toArray()));
            }
        } else {
            $context->info("No data to insert.\n");
        }
    }
}

Artisan::command('staffmetric:analytics', function () {
    $users = \App\Models\User::whereNotNull('email_verified_at')
        ->get();

    $this->line('---------------- started at ' . now()->format('Y-m-d H:i:s') . ' ----------------');

    $this->info('Users to be processed: ' . count($users));
    $this->info("");

    foreach ($users as $user) {
        $employer = $user->employers()->first();
        $this->info(sprintf('Processing %s', $user->email));

        if(! $employer) {
            $this->info("Skip, not found any employers\n");
            continue;
        }

        $this->info("Employee --". json_encode($user->only('id', 'email', 'name')));
        $this->info("Employer --". json_encode($employer->only('id', 'email', 'name')));

        if($employer->pivot->isDisabled()) {
            $this->info("Skip, user is not enabled for current employer\n");
            continue;
        }

        if(! $employer->pivot->isAccepted()) {
            $this->info("Skip, user not accepted invitation\n");
            continue;
        }

        call_user_func_array('calculate_analytics', [$user, $employer, $this]);
    }

    $this->info('');
    $this->info('Total users processed: ' .  count($users));
    $this->info("\n\n");

})->describe('Calculate employees analytics.');
