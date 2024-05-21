<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Console\Application as Artisan;
use Symfony\Component\Finder\Finder;
use Illuminate\Console\Command;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Config;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        if (!DB::getDefaultConnection()) return;
        collect(config('mods'))->filter(function ($data, $modul_azon) {
            return file_exists($data['dir'] . 'schedule.php');
        })->each(function ($data) use ($schedule) {
            try {
                require $data['dir'] . 'schedule.php';
            } catch (\Throwable $th) {
                report($th);
            }
        });
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        Event::listen(CommandStarting::class, function ($event) {
            // Modul azonosító kinyerése
            $class = get_class($this->getArtisan()->all()[$event->command]);
            $class = explode('\\', $class);
            global $global_modul_azon;
            if ($class[0] == 'mod') {
                $global_modul_azon = $class[1];
            } else {
                $global_modul_azon = 'admin';
            }
            // $path = BORDER_PATH_BORDERDOC . getModulAzon();
            // Config::set('filesystems.disks.local.root', $path);
            // Config::set('path.storage', $path);
        });

        // $this->load(__DIR__.'/Commands');
        // require base_path('routes/console.php');

        collect(config('mods'))->filter(function ($data, $modul_azon) {
            return is_dir($data['dir'] . 'commands');
        })->each(function ($data, $modul_azon) {
            try {
                foreach ((new Finder)->in($data['dir'] . 'commands')->files() as $file) {
                    $command = 'mod\\' . $modul_azon . '\\commands\\' . str_replace('.php', '', $file->getFilename());
                    if (is_subclass_of($command, Command::class)) {
                        Artisan::starting(function ($artisan) use ($command, $modul_azon) {
                            $artisan->resolve($command);
                        });
                    }
                }
            } catch (\Throwable $th) {
                report($th);
            }
        });
    }
}
