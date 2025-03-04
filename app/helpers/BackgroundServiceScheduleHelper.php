<?php

namespace App\Helpers;

use App\Core\Datetypes\DateTime;
use App\Core\DB\DatabaseRow;
use App\Entities\SystemServiceEntity;

/**
 * BackgroundServiceScheduleHelper helps with scheduling background services
 * 
 * @author Lukas Velek
 */
class BackgroundServiceScheduleHelper {
    /**
     * Schema:
     * 
     * {
     *  "schedule": {
     *   "days": "mon;tue;wed;thu;fri",
     *   "time": "02"
     *  }
     * }
     * 
     * Meaning:
     * The service will run Mon - Fri at 2 am (time goes 0-24)
     * 
     * {
     *  "schedule": {
     *   "days": "mon;tue;wed;thu;fri",
     *   "every": "60"
     *  }
     * }
     * 
     * Meaning:
     * The service will run Mon - Fri every 60 minutes (1 hour).
     */

    /**
     * Creates JSON schedule from form data
     * 
     * @param array $days Days array from form
     * @param ?string $time Time from from
     * @param string $every Every
     * @return string JSON formatted
     */
    public static function createScheduleFromForm(
        array $days,
        ?string $time,
        string $every
    ): string {
        $result = [];

        $daysEnabled = [];

        foreach($days as $dayName => $day) {
            if($day === true) {
                $daysEnabled[] = $dayName;
            }
        }

        $result['schedule']['days'] = implode(';', $daysEnabled);

        if($time !== null) {
            // time
            $time = explode(':', $time)[0];
            $result['schedule']['time'] = $time;
        } else {
            // every
            $result['schedule']['every'] = $every;
        }

        return json_encode($result);
    }

    /**
     * Checks if a day is enabled in schedule
     * 
     * @param ?string $schedule Schedule
     * @param string $day Day [mon, tue, wed, thu, fri, sat, sun]
     */
    public static function isDayEnabled(?string $schedule, string $day): bool {
        if($schedule === null) {
            return false;
        }
        
        $_schedule = json_decode($schedule, true);

        $days = $_schedule['schedule']['days'];

        return in_array($day, explode(';', $days));
    }

    /**
     * Gets time from schedule
     * 
     * @param ?string $schedule Schedule
     */
    public static function getTime(?string $schedule): ?string {
        if($schedule === null) {
            return null;
        }

        $_schedule = json_decode($schedule, true);

        return $_schedule['schedule']['time'] . ':00';
    }

    /**
     * Checks if schedule uses time or every
     * 
     * @param ?string $schedule Schedule
     */
    public static function usesTime(?string $schedule): bool {
        if($schedule === null) {
            return false;
        }

        $_schedule = json_decode($schedule, true);

        return array_key_exists('time', $_schedule['schedule']);
    }

    /**
     * Returns name of the day from shortcut
     * 
     * @param string $shortcut Shortcut
     */
    public static function getFullDayNameFromShortcut(?string $shortcut): ?string {
        return match($shortcut) {
            'mon' => 'Monday',
            'tue' => 'Tuesday',
            'wed' => 'Wednesday',
            'thu' => 'Thursday',
            'fri' => 'Friday',
            'sat' => 'Saturday',
            'sun' => 'Sunday',
            default => null
        };
    }

    public static function getNextRun(array $schedule, SystemServiceEntity|DatabaseRow $service) {
        if($schedule === null) {
            return null;
        }

        if(array_key_exists('time', $schedule['schedule'])) {
            // time
            $days = $schedule['schedule']['days'];
            
            $todayShortcut = strtolower(date('D'));

            if(in_array($todayShortcut, explode(';', $days))) {
                $pos = array_search(strtolower($todayShortcut), explode(';', $days));

                if(($pos + 1) == count(explode(';', $days))) {
                    // last
                    $next = explode(';', $days)[0];
                } else {
                    // not last
                    $next = explode(';', $days)[$pos + 1];
                }

                $daysArr = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

                $todayIndex = array_search(strtolower($todayShortcut), $daysArr);
                $nextIndex = array_search(strtolower($next), $daysArr);
                if($todayIndex < $nextIndex) {
                    $diff = $nextIndex - $todayIndex;

                    if((int)$schedule['schedule']['time'] > (int)date('H')) {
                        $diff--;
                    }
                } else {
                    $diff = 7 - $todayIndex + $nextIndex; // count until the end of the week plus index of the next day
                }

                $result = new DateTime();
                $result->modify('+' . $diff . 'd');
                $result->format('Y-m-d');
                return $result->getResult();
            }
        } else {
            // every

            $every = $schedule['schedule']['every'];
            $everySecs = $every * 60;

            $dateEnded = null;
            if($service instanceof SystemServiceEntity) {
                $dateEnded = $service->getDateEnded();
            } else {
                $dateEnded = $service->dateEnded;
            }

            $result = new DateTime(strtotime($dateEnded));
            $result->modify('+' . $everySecs . 's');
            $result->format('Y-m-d H:i');
            return $result->getResult();
        }
    }
}

?>