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
     * @param string $every Every
     * @return string JSON formatted
     */
    public static function createScheduleFromForm(
        array $days,
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
        $result['schedule']['every'] = $every;

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

    /**
     * Returns the date of next run
     * 
     * @param ?array $schedule Schedule
     * @param DatabaseRow $service Service database row
     */
    public static function getNextRun(?array $schedule, DatabaseRow $service) {
        if($schedule === null) {
            return null;
        }

        $every = $schedule['schedule']['every'];
        $everySecs = $every * 60;

        $dateEnded = $service->dateEnded;

        if($dateEnded === null) {
            return null;
        }

        $result = new DateTime(strtotime($dateEnded));
        $result->modify('+' . $everySecs . 's');
        $result->format('Y-m-d H:i');
        return $result->getResult();
    }

    /**
     * Returns how often is service run
     * 
     * @param ?string $schedule Schedule
     */
    public static function getEvery(?string $schedule): ?int {
        if($schedule === null) {
            return null;
        }

        $_schedule = json_decode($schedule, true);

        return $_schedule['schedule']['every'];
    }
}

?>