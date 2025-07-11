<?php

namespace QueryBuilder;

use App\Exceptions\AException;
use App\Exceptions\DatabaseExecutionException;
use App\Helpers\DateTimeFormatHelper;
use Exception;

/**
 * QueryBuilder allows users to create an SQL query.
 * 
 * @author Lukas Velek
 * @version 2.0
 */
class QueryBuilder
{
    private const STATE_CLEAN = 1; // QB has not been used yet
    private const STATE_DIRTY = 2; // QB has been already used

    private const USE_BACKTICKS = false;

    private IDbQueriable $conn;
    private ILoggerCallable $logger;
    private string $sql;
    private array $params;
    private mixed $queryResult;
    private string $queryType;
    private array $queryData;
    private bool $hasCustomParams;
    private string $callingMethod;
    private int $openBrackets;
    private int $currentState;
    private bool $hasCustomSQL;

    /**
     * Class constructor
     * 
     * @param IDbQueriable $conn Database connection that can be used to process query
     * @param ILoggerCallable $logger Logger instance
     * @param string $callingMethod Name of the calling method
     */
    public function __construct(IDbQueriable $conn, ILoggerCallable $logger, string $callingMethod = '') {
        $this->conn = $conn;
        $this->logger = $logger;
        $this->callingMethod = $callingMethod;

        $this->clean();
        return $this;
    }

    /**
     * Sets the name of the calling method
     * 
     * @param string $callingMethod Name of the calling method
     */
    public function setCallingMethod(string $callingMethod) {
        $this->callingMethod = $callingMethod;

        return $this;
    }

    /**
     * Returns SQL code for a column where its value is in given values
     * 
     * @param string $column Column name
     * @param array $values Column allowed values
     * @return string SQL code
     */
    public function getColumnInValues(string $column, array $values) {
        if(count($values) == 0) {
            return '1=0';
        }

        $code = $column . ' IN (';

        $i = 0;
        foreach($values as $value) {
            if(is_string($value) && !is_numeric($value)) {
                $value = '\'' . $value . '\'';
            }
            if(($i + 1) == count($values)) {
                $code .= $value;
            } else {
                $code .= $value . ', ';
            }

            $i++;
        }

        $code .= ')';

        return $code;
    }

    /**
     * Returns SQL code for a column where its value is not in given values
     * 
     * @param string $column Column name
     * @param array $values Column allowed values
     * @return string SQL code
     */
    public function getColumnNotInValues(string $column, array $values) {
        if(count($values) == 0) {
            return '1=1';
        }
        
        $code = $column . ' NOT IN (';

        $i = 0;
        foreach($values as $value) {
            if(is_string($value) && !is_numeric($value)) {
                $value = '\'' . $value . '\'';
            }

            if(($i + 1) == count($values)) {
                $code .= $value . ')';
            } else {
                $code .= $value . ', ';
            }

            $i++;
        }

        return $code;
    }

    /**
     * Appends OFFSET
     * 
     * @param int $offset Offset
     */
    public function offset(int $offset) {
        $this->queryData['offset'] = $offset;

        return $this;
    }

    /**
     * Resets OFFSET
     */
    public function resetOffset() {
        if(isset($this->queryData['offset'])) {
            unset($this->queryData['offset']);
        }

        return $this;
    }

    /**
     * Appends DELETE
     */
    public function delete() {
        $this->queryType = 'delete';
        $this->currentState = self::STATE_DIRTY;

        return $this;
    }

    /**
     * Appends UPDATE
     * 
     * @param string $tableName Table name
     */
    public function update(string $tableName) {
        $this->queryType = 'update';
        $this->queryData['table'] = $tableName;
        $this->currentState = self::STATE_DIRTY;

        return $this;
    }

    /**
     * Appends SET
     * 
     * @param array $values Values to be set
     */
    public function set(array $values) {
        if(!isset($this->queryData['values'])) {
            $this->queryData['values'] = $values;
        } else{
            $this->queryData['values'] = array_merge($values, $this->queryData['values']);
        }

        return $this;
    }

    /**
     * Appends SET to NULL
     * 
     * @param array $values Values to be set to null
     */
    public function setNull(array $values) {
        $temp = [];
        foreach($values as $v) {
            $temp[$v] = 'NULL';
        }
        $values = $temp;

        if(!isset($this->queryData['values'])) {
            $this->queryData['values'] = $values;
        } else{
            $this->queryData['values'] = array_merge($values, $this->queryData['values']);
        }

        return $this;
    }

    /**
     * Appends INSERT
     * 
     * @param string $tableName Table name
     * @param array $keys Table columns
     */
    public function insert(string $tableName, array $keys) {
        $this->queryType = 'insert';
        $this->queryData['table'] = $tableName;
        $this->queryData['keys'] = $keys;
        $this->currentState = self::STATE_DIRTY;

        return $this;
    }

    /**
     * Appends VALUES
     * 
     * @param array $values Values
     */
    public function values(array $values) {
        $this->queryData['values'] = $values;

        return $this;
    }

    /**
     * Appends JOIN
     * 
     * @param string $tableName Name of the table being joined
     * @param ?string $alias Alias of the table being joined
     * @param string $joinOn On section of JOIN
     */
    public function join(string $tableName, string $joinOn, ?string $alias = null) {
        $this->queryData['join'] = 'JOIN ' . $tableName . ($alias !== null ? ' ' . $alias : '') . ' ON ' . $joinOn;

        return $this;
    }

    /**
     * Appends SELECT
     * 
     * @param array $keys Table columns
     */
    public function select(array $keys) {
        $this->queryType = 'select';
        $this->queryData['keys'] = $keys;
        $this->currentState = self::STATE_DIRTY;

        return $this;
    }

    /**
     * Appends FROM
     * 
     * @param string $tableName Table name
     * @param ?string $tableNameAlias Table name alias
     */
    public function from(string $tableName, ?string $tableNameAlias = null) {
        $this->queryData['table'] = $tableName;
        
        if($tableNameAlias !== null) {
            $this->queryData['tableAlias'] = $tableNameAlias;
        }

        return $this;
    }

    /**
     * Appends explicit WHERE condition
     * 
     * @param string $where Explicit WHERE condition
     */
    public function whereEx(string $where) {
        $this->queryData['where'] = $where;

        return $this;
    }

    /**
     * Appends WHERE condition
     * 
     * @param string $cond Condition
     * @param array $values Condition parameter values
     */
    public function where(string $cond, array $values = [], bool $useQuotationMarks = true) {
        if(str_contains($cond, '?') && !empty($values)) {
            $count = count(explode('?', $cond));

            if($count != (count($values) + 1)) {
                throw new QueryBuilderException('Number of condition parameters does not equal to the number of passed parameters.');
            }

            $tmp = [];
            foreach($values as $value) {
                if($useQuotationMarks === TRUE) {
                    $tmp[] = "'" . $value . "'";
                } else {
                    $tmp[] = $value;
                }
            }

            $values = $tmp;

            foreach($values as $value) {
                $cond = preg_replace('/\?/', $value, $cond, 1);
            }
        }

        $this->queryData['where'] = '(' . $cond . ')';

        return $this;
    }

    /**
     * Appends AND WHERE condition
     * 
     * @param string $cond Condition
     * @param array $values Condition parameter values
     */
    public function andWhere(string $cond, array $values = [], bool $useQuotationMarks = true) {
        if(!array_key_exists('where', $this->queryData)) {
            $this->queryData['where'] = '';
        }

        if(str_contains($cond, '?') && !empty($values)) {
            $count = count(explode('?', $cond));

            if($count != (count($values) + 1)) {
                throw new QueryBuilderException('Number of condition parameters does not equal to the number of passed parameters.');
            }

            $tmp = [];
            foreach($values as $value) {
                if($useQuotationMarks === TRUE) {
                    $tmp[] = "'" . $value . "'";
                } else {
                    $tmp[] = $value;
                }
            }

            $values = $tmp;

            foreach($values as $value) {
                $cond = preg_replace('/\?/', $value, $cond, 1);
            }
        }

        if(!isset($this->queryData['where']) || ($this->queryData['where'] == '')) {
            $this->queryData['where'] .= '(' . $cond . ')';    
        } else {
            $this->queryData['where'] .= ' AND (' . $cond . ')';
        }

        return $this;
    }

    /**
     * Appends OR WHERE condition
     * 
     * @param string $cond Condition
     * @param array $values Condition parameter values
     */
    public function orWhere(string $cond, array $values = [], bool $useQuotationMarks = true) {
        if(!array_key_exists('where', $this->queryData)) {
            $this->queryData['where'] = '';
        }

        if(str_contains($cond, '?') && !empty($values)) {
            $count = count(explode('?', $cond));

            if($count != (count($values) + 1)) {
                throw new QueryBuilderException('Number of condition parameters does not equal to the number of passed parameters.');
            }

            $tmp = [];
            foreach($values as $value) {
                if($useQuotationMarks === TRUE) {
                    $tmp[] = "'" . $value . "'";
                } else {
                    $tmp[] = $value;
                }
            }

            $values = $tmp;

            foreach($values as $value) {
                $cond = preg_replace('/\?/', $value, $cond, 1);
            }
        }

        if(!isset($this->queryData['where']) || ($this->queryData['where'] == '')) {
            $this->queryData['where'] .= '(' . $cond . ')';    
        } else {
            $this->queryData['where'] .= ' OR (' . $cond . ')';
        }

        return $this;
    }

    /**
     * Appends GROUP BY
     * 
     * @param string $key Grouping column
     */
    public function groupBy(string $key) {
        $this->queryData['group'] = ' GROUP BY ' . $key;

        return $this;
    }

    /**
     * Appends ORDER BY
     * 
     * @param string $key Ordering column
     * @param string $order Ascending (ASC) or descending (DESC)
     */
    public function orderBy(string $key, string $order = 'ASC') {
        if(array_key_exists('order', $this->queryData)) {
            $this->queryData['order'] .= ', ' . (self::USE_BACKTICKS ? '`' : '') . $key . (self::USE_BACKTICKS ? '`' : '') . ' ' . $order;
        } else {
            $this->queryData['order'] = ' ORDER BY ' . (self::USE_BACKTICKS ? '`' : '') . $key . (self::USE_BACKTICKS ? '`' : '') . ' ' . $order;
        }

        return $this;
    }

    /**
     * Appends LIMIT
     * 
     * @param int $limit Limit
     */
    public function limit(int $limit) {
        $this->queryData['limit'] = $limit;

        return $this;
    }

    /**
     * Resets LIMIT
     */
    public function resetLimit() {
        if(isset($this->queryData['limit'])) {
            unset($this->queryData['limit']);
        }

        return $this;
    }

    /**
     * Sets parameters
     * 
     * @param array $params Parameters
     */
    public function setParams(array $params) {
        foreach($params as $k => $v) {
            if($k[0] != ':') {
                $this->params[':' . $k] = "'" . $v . "'";
            } else {
                $this->params[$k] = "'" . $v . "'";
            }
        }

        $this->hasCustomParams = true;

        return $this;
    }

    /**
     * Appends left bracket(s)
     * 
     * @param int $count Bracket count
     */
    public function leftBracket(int $count = 1) {
        $this->openBrackets += $count;

        $this->queryData['where'] .= ' ';

        for($i = 0; $i < $count; $i++) {
            $this->queryData['where'] .= '(';
        }

        $this->queryData['where'] .= ' ';

        return $this;
    }

    /**
     * Appends right bracket(s)
     * 
     * @param int $count Bracket count
     */
    public function rightBracket(int $count = 1) {
        $this->openBrackets -= $count;

        $this->queryData['where'] .= ' ';

        for($i = 0; $i < $count; $i++) {
            $this->queryData['where'] .= ')';
        }

        $this->queryData['where'] .= ' ';

        return $this;
    }

    /**
     * Cleans the QueryBuilder
     */
    public function clean() {
        $this->sql = '';
        $this->params = [];
        $this->queryResult = null;
        $this->queryType = '';
        $this->queryData = [];
        $this->hasCustomParams = false;
        $this->openBrackets = 0;
        $this->currentState = self::STATE_CLEAN;
        $this->hasCustomSQL = false;
    }

    /**
     * Returns the SQL string
     * 
     * @return string SQL string
     */
    public function getSQL() {
        $this->createSQLQuery();

        return $this->sql;
    }

    /**
     * Regenerates SQL query
     */
    public function regenerateSQL(): static {
        $this->createSQLQuery();

        return $this;
    }

    /**
     * Sets the SQL explicitly
     * 
     * @param string $sql SQL string
     */
    public function setSQL(string $sql) {
        $this->sql = $sql;
        $this->hasCustomSQL = true;

        return $this;
    }

    /**
     * Executes the SQL string
     */
    public function execute() {
        $useSafe = false;

        if($this->hasCustomSQL) {
            $this->queryResult = $this->query($this->sql);
            $this->currentState = self::STATE_CLEAN;

            return $this;
        }

        if($this->currentState != self::STATE_DIRTY) {
            throw new QueryBuilderException('No query has been created.');
        }

        if($this->sql === '') {
            $this->createSQLQuery($useSafe);
        }

        if($this->openBrackets > 0) {
            throw new QueryBuilderException('Not all brackets have been closed.', $this->sql);
        }

        if($this->conn === NULL) {
            throw new QueryBuilderException('No connection has been found.');
        }

        try {
            if($useSafe && in_array($this->queryType, ['insert'])) {
                $this->queryResult = $this->query($this->sql, $this->queryData['values']);
            } else {
                $this->queryResult = $this->query($this->sql);
            }
        } catch(AException $e) {
            $this->logException($e);
            
            throw $e;
        }

        $this->currentState = self::STATE_CLEAN;

        return $this;
    }

    /**
     * Fetch the result of the SQL query as associative array
     * 
     * @return mixed Associative array
     */
    public function fetchAssoc() {
        if($this->currentState != self::STATE_CLEAN && $this->queryResult === null) {
            throw new QueryBuilderException('No query has been created.');
        }

        return $this->queryResult->fetch_assoc();
    }

    /**
     * Fetch all results of the SQL query
     * 
     * @return null|mixed Query result array or null
     */
    public function fetchAll() {
        if($this->currentState != self::STATE_CLEAN) {
            return null;
        }

        return $this->queryResult;
    }

    /**
     * Fetch either a single line or a single column value
     * 
     * @param null|string $param If null a single line is returned and if a string is passed than the value of a column named as the string is returned
     * @return null|mixed Null or result
     */
    public function fetch(?string $param = null) {
        $result = null;

        if($this->currentState != self::STATE_CLEAN) {
            return $result;
        }

        if($this->queryResult === NULL || $this->queryResult === FALSE || $this->queryResult === TRUE) {
            return $result;
        }

        if($this->queryResult->num_rows > 1) {
            return $result;
        }

        foreach($this->queryResult as $row) {
            if($param !== NULL) {
                if(array_key_exists($param, $row)) {
                    $result = $row[$param];
                    break;
                } else {
                    break;
                }
            } else {
                $result = $row;
                break;
            }
        }

        return $result;
    }

    /**
     * Fetch an evaluated bool expression from the result
     *
     * @return bool True if the operation was successful or false if not
     */
    public function fetchBool() {
        $result = false;

        if($this->currentState != self::STATE_CLEAN) {
            return $result;
        }

        if($this->queryResult === NULL || $this->queryResult === FALSE) {
            return $result;
        }

        return true;
    }

    /**
     * Creates SQL query
     * 
     * @param bool $useSafe True if safe method should be used or false if not
     */
    private function createSQLQuery(bool $useSafe = false) {
        switch($this->queryType) {
            case 'select':
                $this->createSelectSQLQuery();
                break;

            case 'insert':
                if($useSafe) {
                    $this->createInsertSafeSQLQuery();
                } else {
                    $this->createInsertSQLQuery();
                }
                break;

            case 'update':
                $this->createUpdateSQLQuery();
                break;

            case 'delete':
                $this->createDeleteSQLQuery();
                break;
        }

        $keys = [];
        $values = [];
        foreach($this->params as $k => $v) {
            $keys[] = $k;
            $values[] = $v;
        }

        if($this->hasCustomParams) {
            $this->sql = str_replace($keys, $values, $this->sql);
        }
    }

    /**
     * Creates safe INSERT SQL query
     */
    private function createInsertSafeSQLQuery() {
        $sql = 'INSERT INTO ' . $this->queryData['table'] . ' (';

        $i = 0;
        foreach($this->queryData['keys'] as $key) {
            if(($i + 1) == count($this->queryData['keys'])) {
                $sql .= (self::USE_BACKTICKS ? '`' : '') . $key . (self::USE_BACKTICKS ? '`' : '') . ') VALUES (';
            } else {
                $sql .= (self::USE_BACKTICKS ? '`' : '') . $key . (self::USE_BACKTICKS ? '`' : '') . ', ';
            }

            $i++;
        }

        $i = 0;
        foreach($this->queryData['values'] as $value) {
            if(($i + 1) == count($this->queryData['values'])) {
                $sql .= "?)";
            } else {
                $sql .= "?, ";
            }

            $i++;
        }

        $this->sql = $sql;
    }

    /**
     * Creates DELETE SQL query
     */
    private function createDeleteSQLQuery() {
        $sql = 'DELETE FROM ' . $this->queryData['table'];

        if(array_key_exists('where', $this->queryData)) {
            if(str_starts_with($this->queryData['where'], 'WHERE')) {
                $sql .= ' ' . $this->queryData['where'];
            } else {
                $sql .= ' WHERE ' . $this->queryData['where'];
            }
        }

        $this->sql = $sql;
    }

    /**
     * Creates UPDATE SQL query
     */
    private function createUpdateSQLQuery() {
        $sql = 'UPDATE ' . $this->queryData['table'] . ' SET ';

        $valArray = [];
        foreach($this->queryData['values'] as $key => $value) {
            if($value == 'NULL' || $value == 'current_timestamp()') {
                $valArray[] = $key . ' = ' . $value;
            } else if($value == null) {
                $valArray[] = $key . ' = NULL';
            } else {
                $valArray[] = $key . ' = \'' . $value . '\'';
            }
        }

        $sql .= implode(', ', $valArray) . ' ';

        if(str_starts_with($this->queryData['where'], 'WHERE')) {
            // explicit
            $sql .= ' ' . $this->queryData['where'];
        } else {
            $sql .= ' WHERE ' . $this->queryData['where'];
        }

        $this->sql = $sql;
    }

    private function createInsertSQLQuery() {
        $sql = 'INSERT INTO ' . $this->queryData['table'] . ' (';

        $keyArray = [];
        foreach($this->queryData['keys'] as $key) {
            $keyArray[] = (self::USE_BACKTICKS ? '`' : '') . $key . (self::USE_BACKTICKS ? '`' : '');
        }

        $sql .= implode(', ', $keyArray) . ') VALUES (';

        $valArray = [];
        foreach($this->queryData['values'] as $value) {
            if(is_bool($value)) {
                if($value === true) {
                    $value = '1';
                } else {
                    $value = '0';
                }
            }
            $valArray[] = "'" . $value . "'";
        }

        $sql .= implode(', ', $valArray) . ') ';

        $this->sql = $sql;
    }

    /**
     * Creates SELECT SQL query
     */
    private function createSelectSQLQuery() {
        $sql = 'SELECT ';

        $keyArray = [];
        foreach($this->queryData['keys'] as $key) {
            if($key == '*' || str_starts_with($key, 'COUNT') || str_starts_with($key, 'DISTINCT')) {
                $keyArray[] = $key;
            } else {
                $keyArray[] = (self::USE_BACKTICKS ? '`' : '') . $key . (self::USE_BACKTICKS ? '`' : '');
            }
        }

        $sql .= implode(', ', $keyArray);

        $sql .= ' FROM ' . (self::USE_BACKTICKS ? '`' : '') . $this->queryData['table'] . (self::USE_BACKTICKS ? '`' : '');

        if(isset($this->queryData['tableAlias'])) {
            $sql .= ' ' . $this->queryData['tableAlias'];
        }

        if(isset($this->queryData['join'])) {
            $sql .= ' ' . $this->queryData['join'];
        }

        if(isset($this->queryData['where'])) {
            if(str_starts_with($this->queryData['where'], 'WHERE')) {
                // explicit
                $sql .= ' ' . $this->queryData['where'];
            } else {
                $sql .= ' WHERE ' . $this->queryData['where'];
            }
        }

        if(isset($this->queryData['group'])) {
            $sql .= $this->queryData['group'];
        }

        if(isset($this->queryData['order'])) {
            $sql .= $this->queryData['order'];
        }

        if(isset($this->queryData['limit'])) {
            $sql .= ' LIMIT ' . $this->queryData['limit'];
        }

        if(isset($this->queryData['offset'])) {
            $sql .= ' OFFSET ' . $this->queryData['offset'];
        }

        $this->sql = $sql;
    }

    /**
     * Logs an SQL string (with the number of milliseconds it took)
     * 
     * @param null|int|float $msTaken Milliseconds the SQL query took
     */
    private function log(null|int|float $msTaken = null, Exception $e) {
        if($this->logger !== NULL) {
            $this->logger->sql($this->sql, $this->callingMethod, $msTaken, $e);
        }
    }

    /**
     * Runs a SQL query with the given string and parameters
     * 
     * @param string $sql SQL query
     * @param array $params SQL parameters
     * @return mixed SQL query result
     */
    private function query(string $sql, array $params = []) {
        $tsStart = null;
        $tsEnd = null;

        $q = function(string $sql, array $params) use (&$tsStart, &$tsEnd) {
            $tsStart = hrtime(true);
            try {
                $result = $this->conn->query($sql, $params);
            } catch(\mysqli_sql_exception $e) {
                throw new DatabaseExecutionException($e, $sql, $e);
            }
            $tsEnd = hrtime(true);
            return $result;
        };
        
        $result = $q($sql, $params);
        
        $diff = $tsEnd - $tsStart;

        $diff = DateTimeFormatHelper::convertNsToMs($diff);

        $e = new Exception;

        $this->log($diff, $e);

        return $result;
    }

    /**
     * Logs an SQL exception
     * 
     * @param Exception $e Exception generated
     */
    private function logException(AException|Exception $e) {
        if($this->logger !== null) {
            $this->logger->exception($e, $this->callingMethod);
        }
    }

    /**
     * Exports the query data
     * 
     * @return array<string, mixed> Query data
     */
    public function export() {
        return [
            'params' => $this->params,
            'queryData' => $this->queryData,
            'queryType' => $this->queryType
        ];
    }

    /**
     * Imports the query data
     * 
     * @param array<string, mixed> Query data
     */
    public function import(array $data) {
        $this->queryData = $data['queryData'];
        $this->queryData = $data['params'];
        $this->queryType = $data['queryType'];

        $this->currentState = self::STATE_DIRTY;

        return $this;
    }
}

?>