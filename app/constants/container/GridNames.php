<?php

namespace App\Constants\Container;

use App\Constants\AConstant;

class GridNames extends AConstant {
    public const DOCUMENTS_GRID = 'documentsGrid';
    public const PROCESS_GRID = 'processGrid';
    public const PROCESS_REPORTS_GRID = 'processReportsGrid';

    public static function toString($key): ?string {
        $result = match($key) {
            self::DOCUMENTS_GRID => 'Documents grid',
            self::PROCESS_GRID => 'Process grid',
            self::PROCESS_REPORTS_GRID => 'Process reports grid',
            default => null
        };

        if($result !== null) {
            return $result;
        }

        foreach(self::getAll() as $name => $title) {
            if(str_contains($key, $name)) {
                return $title;
            }
        }

        return '#ERROR';
    }
}

?>