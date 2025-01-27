<?php

namespace App\Helpers;

use App\Core\Datetypes\DateTime;
use App\Core\FileManager;
use App\Modules\TemplateObject;
use Throwable;

class ExceptionHelper {
    /**
     * Saves exception to file
     * 
     * @param ?Throwable $e Throwable instance
     * @param string $hash
     */
    public static function saveExceptionToFile(?Throwable $e, string $hash) {
        global $app;

        if($app === null) {
            return;
        }

        $date = new DateTime();
        $date->format('Y-m-d_H-i-s');

        $filePath = 'exception_' . $date . '_' . $hash . '.html';

        FileManager::saveFile(APP_ABSOLUTE_DIR . LOG_DIR, $filePath, self::createHTML($e, get_class($e), $e->getMessage()));
    }

    /**
     * Creates exception HTML
     */
    private static function createHTML(?Throwable $e, string $name, string $message): string {
        $templateContent = FileManager::loadFile(APP_ABSOLUTE_DIR . 'app\\exceptions\\templates\\common.html');
        $to = new TemplateObject($templateContent);

        $trace = $e->getTrace();
        $callstack = '';

        $i = 1;
        foreach($trace as $t) {
            $script = $t['file'];
            $line = $t['line'];
            $function = $t['function'];
            $args = $t['args'] ?? null;
            $argString = '';

            if(!is_array($args) || (count($args) > 1 && is_object($args[0]))) {
                $argString = '[\'' . var_export($args, true) . '\']';
            } else {
                if(count($args) > 1) {
                    $tmp = [];
                    foreach($args as $arg) {
                        $tmp[] = @var_export($arg, true);
                    }
                    $args = $tmp;
                    //$argString = '[\'' . implode('\', \'', $args) . '\']';
                }
            }

            $line = '#' . $i . ' Script: \'' . $script . '\' on line ' . $line . ' - method: ' . $function . '()';

            $callstack .= $line . "<br>";

            $i++;
        }

        $to->name = $name;
        $to->message = $message;
        $to->callstack = $callstack;

        $to->render();
        return $to->getRenderedContent();
    }
}

?>