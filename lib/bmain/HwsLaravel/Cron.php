<?php

namespace hws;

class Cron
{

    static public function getJobs()
    {
        if (defined('BORDER_EMU')) {
            if (is_file('\tmp\cron_jobs.txt')) {
                $array = explode("\n", file_get_contents('\tmp\cron_jobs.txt'));
            } else {
                $array = [];
            }
        } else {
            $array = explode("\n", trim(shell_exec('crontab -l')));
        }

        foreach ($array as $key => $item) {
            $array[$key] = trim($array[$key]);
            if (empty($array[$key])) unset($array[$key]);
        }
        return $array;
    }

    static public function saveJobs($jobs = array())
    {
        if (defined('BORDER_EMU')) return file_put_contents('\tmp\cron_jobs.txt', implode("\n", $jobs));
        return shell_exec('echo "' . implode("\n", $jobs) . '" | crontab -');
    }

    // static public function addJob($job = '') {
    // 	$jobs = self::getJobs();
    // 	if(in_array($job, $jobs))return false;
    // 	$jobs[] = $job;
    // 	return self::saveJobs($jobs);
    // }

    static public function addJob($timer, $azon, $artisan_command)
    {
        $php = 'php'.preg_replace('#^([1-9]*)\.([1-9]*)\..*#','$1.$2',phpversion());

        if(shell_exec($php.' -v') == null) $php = "php";

        $job = "\n" . trim($timer);
        $job .= ' ' . $php . ' ' . base_path() . DIRECTORY_SEPARATOR . 'artisan ' . $artisan_command;
        $job .= ' #' . trim($azon) . "#\n";

        $jobs = self::getJobs();
        if (in_array($job, $jobs)) return false;

        self::remove($jobs, $azon);

        $jobs[] = $job;
        return self::saveJobs($jobs);
    }

    static public function removeJob($azon)
    {
        $jobs = self::getJobs();
        self::remove($jobs, $azon);
        return self::saveJobs($jobs);
    }

    static public function remove(&$jobs, $azon)
    {
        $to_delete = preg_grep('/\#' . trim($azon) . '\#/', $jobs);
        if (count($to_delete)) foreach ($to_delete as $key => $v) {
            unset($jobs[$key]);
        }
    }
}
