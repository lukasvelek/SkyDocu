<?php

namespace QueryBuilder;

use Exception;

/**
 * ILoggerCallable is an interface that must be implemented by a class that allows logging.
 * 
 * @author Lukas Velek
 */
interface ILoggerCallable {
    function sql(string $sql, string $method, null|int|float $msTaken, ?Exception $e = null);
    function exception(Exception $e, string $method);
}

?>