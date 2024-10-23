<?php

//
//                      LOGGING
//

/**
 * Application log level
 * 
 * 0 - no log
 * 1 - errors only
 * 2 - warnings and errors
 * 3 - everything except for cache
 * 4 - everything with cache
 */
define('LOG_LEVEL', 4);
/**
 * SQL queries logging
 * 
 * 0 - off
 * 1 - on
 */
define('SQL_LOG_LEVEL', 1);
/**
 * Stopwatch logging
 * 
 * 0 - off
 * 1 - on
 */
define('LOG_STOPWATCH', 1);

//
//                      DIRECTORIES
//

define('LOG_DIR', 'logs\\');
define('CACHE_DIR', 'cache\\');
define('CONTAINERS_DIR', 'containers\\');

define('APP_ABSOLUTE_DIR', 'C:\\xampp\\htdocs\\skydocu\\');
define('PHP_ABSOLUTE_DIR', 'C:\\xampp\\php\\');

//
//                      GENERAL
//

define('APP_HOSTNAME', 'localhost');
define('GRID_SIZE', 20);
define('MAX_GRID_EXPORT_SIZE', 100);

//
//                      DATABASE
//

define('DB_SERVER', 'localhost');
define('DB_PORT', '');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_MASTER_NAME', 'skydocu_master');

?>