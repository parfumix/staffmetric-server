<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel {
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule) {
        
        // notify if uncategorized apps
        $schedule->command('staffmetric:uncategorized')
            ->weekly()->at('09:00');
            
        // on hourly basis update analytics table
        $schedule->command('staffmetric:analytics')
            ->hourly()
            ->appendOutputTo( storage_path('logs/analytics.log') );

        // calculate top apps each day
        $schedule->command('staffmetric:top_apps')
            ->daily()
            ->appendOutputTo( storage_path('top_apps.log') );

        // each day add new apps to apps table
        $schedule->command('staffmetric:apps')
            ->daily()
            ->appendOutputTo( storage_path('apps.log') );
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands() {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
