<?php

namespace App\Core\Datetypes;

use App\Exceptions\GeneralException;

/**
 * DateTime class that allows performing advanced function on the date.
 * 
 * @author Lukas Velek
 */
class DateTime {
    private int $timestamp;
    private string $format;

    /**
     * Class constructor. It has no parameters and it only sets the implicit values to the parameters.
     */
    public function __construct(?int $timestamp = null) {
        $this->timestamp = $timestamp ?? time();
        $this->format = 'Y-m-d H:i:s';
    }

    /**
     * Sets the datetime format. Format from PHP date() function is used.
     * 
     * @param string $format
     */
    public function format(string $format) {
        $this->format = $format;
    }

    /**
     * Returns the datetime result
     * 
     * @return string Datetime result
     */
    public function getResult() {
        return date($this->format, $this->timestamp);
    }

    /**
     * Modifies the datetime.
     * 
     * s - seconds
     * i - minutes
     * h - hours
     * d - days
     * m - months
     * 
     * Examples:
     * - to add 1 hour -> use +1h
     * - to remove 1 hour -> use -1h
     * - to add 3 days -> use +3d
     * - to remove 10 days -> use -10d
     * - to add 2 months -> use +2m
     */
    public function modify(string $code) {
        $func = null;

        if($code[0] == '+') {
            $func = 1;
        } else if($code[0] == '-') {
            $func = 0;
        } else {
            throw new GeneralException('Unknown datetime modification operation.');
        }

        $count = null;

        $i = 1;
        while(is_numeric($code[$i])) {
            $count .= $code[$i];
            $i++;
        }

        if($count === null) {
            throw new GeneralException('Incorrect datetime modification value passed.');
        }

        $type = $code[strlen($code) - 1];

        if(is_numeric($type)) {
            throw new GeneralException('Incorrect datetime modification value passed.');
        }

        $this->internalModify($func, $count, $type);
    }

    /**
     * Processes modification on values
     * 
     * @param bool $add True if time should be added or false if it should be substracted
     * @param int $count Count to add or substract
     * @param string $type Type of count to add or substract
     */
    private function internalModify(bool $add, int $count, string $type) {
        $m = 1;

        switch($type) {
            case 's':
                break;
                
            //case 'm':
            case 'i':
                $m = 60;
                break;
                
            case 'h':
                $m = 60 * 60;
                break;

            case 'd':
                $m = 60 * 60 * 24;
                break;

            case 'm':
                $m = 60 * 60 * 24 * 30;
                break;
        }

        if($add) {
            $this->timestamp += $count * $m;
        } else {
            $this->timestamp += -($count * $m);
        }
    }

    /**
     * Converts self to string
     * 
     * @return string String representation of self
     */
    public function __toString() {
        return $this->getResult();
    }

    /**
     * Returns current timestamp in the standard format (Y-m-d H:i:s).
     * Alias of time().
     * 
     * @return string Current timestamp
     */
    public static function now() {
        $now = new self();

        return $now->getResult();
    }
}

?>