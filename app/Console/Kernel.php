<?php

namespace App\Console;

use App\Jobs\CheckCleanup;
use App\Jobs\CheckQueueFailedJobs;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
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
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('exam:assign_cronjob')->everyMinute()->withoutOverlapping();
        $schedule->command('exam:checkout_cronjob')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('exam:update_progress --bulk=1')->hourly()->withoutOverlapping();
        $schedule->command('backup:cronjob')->everyMinute()->withoutOverlapping();
        $schedule->command('hr:update_status')->everyTenMinutes()->withoutOverlapping();
        $schedule->command('hr:update_status --ignore_time=1')->hourly()->withoutOverlapping();
        $schedule->command('user:delete_expired_token')->dailyAt('04:00')->withoutOverlapping();
        $schedule->command('claim:settle')->hourly()->when(function () {
            return Carbon::now()->format('d') == '01';
        })->withoutOverlapping();
        $schedule->command('meilisearch:import')->weeklyOn(1, "03:00")->withoutOverlapping();
        $schedule->command('torrent:load_pieces_hash')->dailyAt("01:00")->withoutOverlapping();
        $schedule->job(new CheckQueueFailedJobs())->everySixHours()->withoutOverlapping();

        $this->registerScheduleCleanup($schedule);
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    private function registerScheduleCleanup(Schedule $schedule): void
    {
        $interval = get_setting("main.autoclean_interval_one");
        if (!$interval || $interval < 60) {
            $interval = 7200;
        }
        $schedule->job(new CheckCleanup())
            ->cron(sprintf("*/%d * * * *", ceil($interval/60)))
            ->withoutOverlapping();
    }
}
