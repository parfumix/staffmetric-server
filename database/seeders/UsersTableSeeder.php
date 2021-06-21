<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        $admin = \App\Models\User::factory()->create();
        $admin->assignRole(\Spatie\Permission\Models\Role::where('name', 'admin')->first());
        
        \App\Models\User::factory(5)->create()->each(function($user) use($admin) {
            $this->generateAnalyticsData($admin, $user);
        });
    }

    public function generateAnalyticsData(\App\Models\User $admin, \App\Models\User $user) {
        $start_at = now()->subWeek();
        $end_at = now()->addMonth();

        $date_range = [];
        for($date = $start_at->copy(); $date->lte($end_at); $date->addDay()) {
            $date_range[] = $date->copy();
        }
        $hours_range = hours_range(9, rand(18, 19), 'H');

        $analytics_to_insert = [];
        $top_apps_to_insert = [];

        $admin->employees()
            ->syncWithoutDetaching([$user->id => ['status' => 'accepted']]);

        $device = \App\Models\Device::factory()->create([
            'user_id' => $user->id
        ]);

        $categories = \App\Models\Category::all()->pluck('id', 'title');

        $random_social_providers = \App\Models\App::where('category_id', $categories['Social'])->pluck('category_id', 'name');
        $random_email_providers = \App\Models\App::where('category_id', $categories['Email'])->pluck('category_id', 'name');

        foreach ($date_range as $date) {
            foreach ($hours_range as $hour => $formatted) {
                $date->setTime($hour, 0, 0);

                $tota_secs = rand(2500, 3600);

                $productive_percentage = rand(30, 50);
                $productive_secs = ($productive_percentage * $tota_secs) / 100;
                $non_productive_secs = (($productive_percentage / 2) * $tota_secs) / 100;
                $neutral_secs = (($productive_percentage / 2) * $tota_secs) / 100;

                $email_secs = (rand(30, 60) * $productive_secs) / 100;
                $social_network_secs = $non_productive_secs;

                $idle_count = rand(1, 5);
                $idle_secs = rand(2500, 3600);;
                $meeting_secs = null;

                $overtime_secs = $hour > 18 ? $tota_secs : null;

                $web_usage_percentage = rand(30, 60);
                $web_usage = ($web_usage_percentage * $tota_secs) / 100;
                $app_usage = ((100 - $web_usage_percentage) * $tota_secs) / 100;

                $analytics_to_insert[] = [
                    'project_id' => null,
                    'device_id' => $device->id,
                    'user_id' => $user->id,
                    'employer_id' => $admin->id,

                    'total_secs' => $tota_secs,
                    'productive_secs' => $productive_secs,
                    'non_productive_secs' => $non_productive_secs,
                    'neutral_secs' => $neutral_secs,
                    'idle_secs' => $idle_secs,
                    'idle_count' => $idle_count,
                    'email_secs' => $email_secs,
                    'office_secs' => $tota_secs,
                    'overtime_secs' => $overtime_secs,
                    'meetings_secs' => $meeting_secs,
                    'social_network_secs' => $social_network_secs,
                    'app_usage' => $app_usage,
                    'web_usage' => $web_usage,
                    'employee_time' => $date->format('Y-m-d H:i:s'),
                    'created_at' => $date->format('Y-m-d H:i:s'),
                    'updated_at' => $date->format('Y-m-d H:i:s'),
                ];

                if($email_secs && false) {
                    $random_provider = $random_email_providers->keys()->random();
                    $random_category_provider = $random_email_providers[$random_provider];
                    $top_apps_to_insert[] = [
                        'last_index' => null,
                        'project_id' => null,
                        'user_id' => $user->id,
                        'category_id' => $random_category_provider ?? null,
                        'app' => $random_provider,
                        'duration' => $email_secs,
                        'created_at' => $date->format('Y-m-d H:i:s'),
                        'updated_at' => $date->format('Y-m-d H:i:s'),
                    ];
                }

                if($social_network_secs && false) {
                    $random_provider = $random_social_providers->keys()->random();
                    $random_category_provider = $random_social_providers[$random_provider];
                    $top_apps_to_insert[] = [
                        'last_idle_index' => null,
                        'app' => $random_provider,
                        'project_id' => null,
                        'user_id' => $user->id,
                        'category_id' => $random_category_provider ?? null,
                        'duration' => $social_network_secs,
                        'created_at' => $date->format('Y-m-d H:i:s'),
                        'updated_at' => $date->format('Y-m-d H:i:s'),
                    ];
                }
            }
        }

        $user->topApps()->createMany($top_apps_to_insert);
        $user->analytics()->createMany($analytics_to_insert);
    }
}
