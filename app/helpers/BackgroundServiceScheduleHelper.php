<?php

namespace App\Helpers;

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
     * The service will run Mon - Fri (Mon = 0, Tue = 1, ...) at 2 am (time goes 0-24)
     */

    /**
     * Creates JSON schedule from form data
     * 
     * @param array $days Days array from form
     * @param string $time Time from from
     * @return string JSON formatted
     */
    public static function createScheduleFromForm(
        array $days,
        string $time
    ): string {
        $result = [];

        $daysEnabled = [];

        foreach($days as $dayName => $day) {
            if($day === true) {
                $daysEnabled[] = $dayName;
            }
        }

        $time = explode(':', $time)[0];

        $result['schedule']['days'] = implode(';', $daysEnabled);
        $result['schedule']['time'] = $time;

        return json_encode($result);
    }

    /**
     * Checks if a day is enabled in schedule
     * 
     * @param string $schedule Schedule
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
}

?>