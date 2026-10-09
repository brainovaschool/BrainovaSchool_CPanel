<?php

namespace App\Console;

use Illuminate\Support\Facades\Artisan;
use App\Models\StudentAbsentNotification;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\ExamResultGenerate::class,
        Commands\MainMigrateSeed::class,
        \App\Console\Commands\ModuleInstall::class,
        \App\Console\Commands\Portal\TaskDeadlineCron::class,
    ];
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('exam:result-generate')
                 ->everyMinute();

        $schedule->command('attendance:cron')
                 ->everyMinute();

        $schedule->command('portal:task-deadlines')
                 ->everyMinute();
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
}
