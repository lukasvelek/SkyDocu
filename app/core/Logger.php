<?php

namespace App\Logger;

use App\Core\ApplicationDatabaseLogger;
use App\Core\Datetypes\DateTime;
use App\Core\FileManager;
use App\Helpers\DateTimeFormatHelper;
use Exception;
use QueryBuilder\ILoggerCallable;

/**
 * Logger allows logging information, warnings, errors
 * 
 * @author Lukas Velek
 */
class Logger implements ILoggerCallable {
    public const LOG_INFO = 'info';
    public const LOG_WARNING = 'warning';
    public const LOG_ERROR = 'error';
    public const LOG_SQL = 'sql';
    public const LOG_STOPWATCH = 'stopwatch';
    public const LOG_EXCEPTION = 'exception';
    public const LOG_CACHE = 'cache';

    private int $logLevel;
    private int $sqlLogLevel;
    private ?string $specialFilename;
    private int $stopwatchLogLevel;
    private ?string $containerId;
    private ?ApplicationDatabaseLogger $appDbLogger = null;

    /**
     * Class constructor
     */
    public function __construct() {
        $this->sqlLogLevel = SQL_LOG_LEVEL;
        $this->logLevel = LOG_LEVEL;
        $this->specialFilename = null;
        $this->stopwatchLogLevel = LOG_STOPWATCH;
        $this->containerId = null;
    }

    /**
     * Sets applications database logger instance
     * 
     * @param ApplicationDatabaseLogger $appDbLogger ApplicationDatabaseLogger instance
     */
    public function setApplicationDatabaseLogger(ApplicationDatabaseLogger $appDbLogger) {
        $this->appDbLogger = $appDbLogger;
    }

    /**
     * Measures the time taken to process given function and returns the result of the given function
     * 
     * @param callback $function Function to measure
     * @param string $method Calling method name
     * @return mixed Result of $function
     */
    public function stopwatch(callable $function, string $method) {
        $time = hrtime(true);

        $result = $function();

        $diff = hrtime(true) - $time;

        $diff = DateTimeFormatHelper::convertNsToMs($diff);

        $this->log($method, 'Time taken: ' . $diff . ' ms', self::LOG_STOPWATCH);

        return $result;
    }

    /**
     * Logs information - for services only
     * 
     * @param string $text Text
     * @param string $serviceName Service name
     */
    public function serviceInfo(string $text, string $serviceName): bool {
        return $this->logService($serviceName, $text, self::LOG_INFO);
    }

    /**
     * Logs error - for services only
     * 
     * @param string $text Text
     * @param string $serviceName Service name
     */
    public function serviceError(string $text, string $serviceName): bool {
        return $this->logService($serviceName, $text, self::LOG_ERROR);
    }

    /**
     * Saves service message to the service log file
     * 
     * @param string $serviceName Service name
     * @param string $text Text
     * @param string $type Message type
     */
    private function logService(string $serviceName, string $text, string $type = self::LOG_INFO): bool {
        $oldSpecialFilename = $this->specialFilename;
        $this->specialFilename = 'service-log';

        $date = new DateTime();
        $_text = '[' . $date . '] [' . strtoupper($type) . '] ' . $serviceName . ': ' . $text;

        $result = $this->writeLog($text, $serviceName, $type, $_text);

        $this->specialFilename = $oldSpecialFilename;

        return $result;
    }

    /**
     * Logs SQL query
     * 
     * @param string $sqlQuery SQL string
     * @param string $method Calling method
     * @param null|int|float $msTaken Milliseconds taken
     * @param ?Exception $e Exception for call trace
     */
    public function sql(string $sql, string $method, null|int|float $msTaken, ?Exception $e = null) {
        $this->logSQL($method, $sql, ($msTaken ?? 0.0), $e);
    }

    /**
     * Saves SQL query log to the SQL log file
     * 
     * @param string $method Calling method
     * @param string $sql SQL string
     * @param null|int|float $msTaken Milliseconds taken
     * @param ?Exception $e Exception for call trace
     */
    private function logSQL(string $method, string $sql, null|int|float $msTaken, ?Exception $e = null) {
        $date = new DateTime();
        $_newText = '[' . $date . '] [' . strtoupper(self::LOG_SQL) . '] [' . (float)($msTaken) . ' ms] ' . $method . '(): ' . $sql;

        if(SQL_LOG_LEVEL > 1 && $e !== null) {
            $_newText .= "\r\n" . 'Stack trace: ' . "\r\n" . $e->getTraceAsString();
        }

        if($this->sqlLogLevel >= 1) {
            $oldSpecialFilename = $this->specialFilename;
            $this->specialFilename = 'sql-log';
            $this->writeLog($sql, $method, strtoupper(self::LOG_SQL), $_newText, false, false);
            $this->specialFilename = $oldSpecialFilename;
        }
    }

    /**
     * Logs information
     * 
     * @param string $text Text
     * @param string $method Calling method
     */
    public function info(string $text, string $method) {
        $this->log($method, $text);
    }

    /**
     * Logs warning
     * 
     * @param string $text Text
     * @param string $method Calling method
     */
    public function warning(string $text, string $method) {
        $this->log($method, $text, self::LOG_WARNING);
    }

    /**
     * Logs error
     * 
     * @param string $text Text
     * @param string $method Calling method
     */
    public function error(string $text, string $method) {
        $this->log($method, $text, self::LOG_ERROR);
    }

    /**
     * Logs exception
     * 
     * @param Exception $e Exception instance
     * @param string $method Calling method
     */
    public function exception(Exception $e, string $method) {
        $text = 'Exception: ' . $e->getMessage() . '. Call stack: ' . $e->getTraceAsString();

        $this->log($method, $text, self::LOG_EXCEPTION);
    }

    /**
     * Saves message to the log file
     * 
     * @param string $method Calling method
     * @param string $text Text
     * @param string $type Message type
     * @return bool True on success or false on failure
     */
    protected function log(string $method, string $text, string $type = self::LOG_INFO) {
        $date = new DateTime();
        $_text = '[' . $date . '] [' . strtoupper($type) . '] ' . $method . '(): ' . $text;

        $result = true;
        switch($type) {
            case self::LOG_STOPWATCH:
                if($this->stopwatchLogLevel >= 1) {
                    $result = $this->writeLog($text, $method, $type, $_text);
                }
                break;

            case self::LOG_CACHE:
                if($this->logLevel >= 4) {
                    $result = $this->writeLog($text, $method, $type, $_text);
                }
                break;

            case self::LOG_INFO:
                if($this->logLevel >= 3) {
                    $result = $this->writeLog($text, $method, $type, $_text);
                }
                break;

            case self::LOG_WARNING:
                if($this->logLevel >= 2) {
                    $result = $this->writeLog($text, $method, $type, $_text);
                }
                break;
            
            case self::LOG_ERROR:
                if($this->logLevel >= 1) {
                    $result = $this->writeLog($text, $method, $type, $_text);
                }
                break;

            default:
                $result = false;
                break;
        }

        return $result;
    }

    /**
     * Sets custom filename. If set to null then no custom filename is set.
     * 
     * @param null|string $filename Custom filename
     */
    public function setFilename(?string $filename) {
        $this->specialFilename = $filename;
    }

    /**
     * Saves log message to the file
     * 
     * @param string $rawText Raw log message
     * @param string $method Method
     * @param string $type Type
     * @param string $text Log message
     * @param bool $addStackTrace Add stack trace?
     * @param bool $saveToDatabase Save to database?
     * @return bool True on success or false on failure
     */
    private function writeLog(string $rawText, string $method, string $type, string $text, bool $addStackTrace = true, bool $saveToDatabase = true) {
        $folder = APP_ABSOLUTE_DIR . LOG_DIR;

        if($this->containerId !== null) {
            $folder .= 'containers\\' . $this->containerId . '\\';
        }

        $date = new DateTime();
        $date->format('Y-m-d');
        
        if($this->specialFilename !== null) {
            $file = $this->specialFilename . '_' . $date . '.log';
        } else {
            $file = 'log_' . $date . '.log';
        }

        if(!FileManager::folderExists($folder)) {
            FileManager::createFolder($folder, true);
        }

        $e = null;
        if(LOG_LEVEL >= 5 && $addStackTrace) {
            $e = new Exception;

            $text .= "\r\n Stack trace: \r\n" . $e->getTraceAsString();
        }

        if($saveToDatabase && ($this->appDbLogger !== null)) {
            $rawText = str_replace('\\', '\\\\', $rawText);
            $stackTrace = null;
            if($e !== null) {
                $stackTrace = str_replace('\\', '\\\\', $e->getTraceAsString());
                $stackTrace = htmlspecialchars($stackTrace);
            }
            $method = str_replace('\\', '\\\\', $method);
            $this->appDbLogger->log(
                htmlspecialchars($rawText),
                $method . '()',
                $stackTrace,
                $type
            );
        }
        $result = FileManager::saveFile($folder, $file, $text . "\r\n", false, true);

        if($result !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Sets the container ID
     * 
     * @param string $containerId Container ID
     */
    public function setContainerId(string $containerId) {
        $this->containerId = $containerId;
    }
}

?>