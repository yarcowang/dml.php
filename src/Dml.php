<?php
/**
 * Proj. dml.php
 *
 * @author Yarco Wang <yarco.wang@gmail.com>
 * @since 17/1/12 下午9:49
 */

namespace Yarco\Dml;


class Dml extends \PDO implements IDml
{
    protected $_tables = [];

    /**
     * get tables from some mysql database
     *
     * @return array
     */
    public function tables() : array
    {
        $tables = $this->query('SHOW TABLES');
        if (!$tables) {
            throw new \PDOException($this->errorInfo()[2]);
        }

        $t = [];
        foreach($tables as $table) {
            $t[] = $table[0];
        }
        return $t;
    }

    /**
     * get fields info from some table
     *
     * @param string $table
     * @param bool $refresh if true, it will force to reload the information from db (you may cache it somewhere)
     * @return array
     */
    public function describe(string $table, bool $refresh = false) : array
    {
        if (!$refresh && isset($this->_tables[$table])) {
            return $this->_tables[$table];
        }

        $fields = $this->query('DESCRIBE ' . $table);
        if (!$fields) {
            throw new \PDOException($this->errorInfo()[2]);
        }

        $p = & $this->_tables[$table];
        $p['pk'] = false;
        $p['ts'] = [];
        foreach($fields as $field) {
            $p['ts'][$field['Field']] = $field['Type'];
            if (!$p['pk'] && $field['Key'] === 'PRI') { // primary key, always use the first one
                $p['pk'] = $field['Field'];
            }
        }
        return $p;
    }

    /**
     * get tables and fields info from some database, if supplied $tables, then it would skip those steps
     *
     * @param array $tables
     * @return array
     */
    public function init(array $tables = []) : array
    {
        if ($tables) {
            $this->_tables = $tables; // get definitions from external
            return $this->_tables;
        }

        foreach($this->tables() as $table) {
            $this->_tables[$table] = $this->describe($table, true);
        }

        return $this->_tables;
    }

    /**
     * get the first primary key name
     *
     * @param string $table
     * @return string
     */
    public function getPkName(string $table) : string
    {
        if (!isset($this->_tables[$table])) {
            throw new \PDOException("$table not found");
        }
        if (! ($pk = $this->_tables[$table]['pk'])) { // no primary key
            throw new \PDOException("$table has no primary key");
        }
        return $pk;
    }

    /**
     * comparing the supplied data and fields, get the valid kv pairs, also prepare the PDO version of the data keys in $placeholders :k (prefix with colon)
     *
     * @param string $table
     * @param array $data
     * @param array $placeholders
     * @return array
     */
    protected function getValidData(string $table, array $data, & $placeholders = []) : array
    {
        $valid_data = array_intersect_key($data, $this->_tables[$table]['ts']);
        func_num_args() === 3 && $placeholders = array_map(function($v) { return ':' . $v; }, array_keys($valid_data));
        return $valid_data;
    }

    /**
     * get single record by id (the table must have primary key), return PDOStatement
     *
     * @param string $table
     * @param int $id
     * @return \PDOStatement
     */
    public function get(string $table, int $id) : \PDOStatement
    {
        if (!isset($this->_tables[$table])) {
            throw new \PDOException("$table not found");
        }
        if (! ($pk = $this->_tables[$table]['pk'])) { // no primary key
            throw new \PDOException("$table has no primary key, try find method");
        }

        return $this->query("SELECT * FROM $table WHERE $pk=$id");
    }

    /**
     * remove single record by id (as get), return the affected count
     *
     * @param string $table
     * @param int $id
     * @return int|false
     */
    public function rm(string $table, int $id) : int
    {
        if (!isset($this->_tables[$table])) {
            throw new \PDOException("$table not found");
        }
        if (! ($pk = $this->_tables[$table]['pk'])) { // no primary key
            throw new \PDOException("$table has no primary key");
        }

        return $this->exec("DELETE FROM $table WHERE $pk=$id");
    }

    /**
     * update data by id or for all
     *
     * @param string $table
     * @param array $data
     * @param int|null $id
     * @return bool
     */
    public function update(string $table, array $data, int $id = null)
    {
        if (!isset($this->_tables[$table])) {
            throw new \PDOException("$table not found");
        }
        if (! ($pk = $this->_tables[$table]['pk'])) { // no primary key
            throw new \PDOException("$table has no primary key");
        }

        $where = $id !== null ? "WHERE $pk=" . intval($id) : '';
        $valid_data = $this->getValidData($table, $data, $placeholders);
        $update = array_map(function($i0, $i1) { return "$i0=$i1"; }, array_keys($valid_data), $placeholders);
        $update = implode(',', $update);
        $sql = "UPDATE $table SET $update $where";
        $stmt = $this->prepare($sql);
        if (!$stmt instanceof \PDOStatement) {
            throw new \PDOException($this->errorInfo()[2]);
        }

        $ret = $stmt->execute($valid_data);
        if ($ret === false) {
            throw new \PDOException($stmt->errorInfo()[2]);
        }
        return $ret;
    }

    /**
     * find records by supplying the where statement and data
     * ex.:
     *  $where = 'id=:id'
     *  $data = ['id' => 1]
     *
     * @param string $table
     * @param string $where
     * @param array $data
     * @return \PDOStatement
     */
    public function find(string $table, string $where = '', array $data = []) : \PDOStatement
    {
        if (!isset($this->_tables[$table])) {
            throw new \PDOException("$table not found");
        }
        $where = $where ? 'WHERE ' . $where : '';
        $valid_data = $this->getValidData($table, $data);
        $sql = "SELECT * FROM $table $where";
        $stmt = $this->prepare($sql);
        if (!$stmt instanceof \PDOStatement) {
            throw new \PDOException($this->errorInfo()[2]);
        }

        $ret = $stmt->execute($valid_data);
        if ($ret === false) {
            throw new \PDOException($stmt->errorInfo()[2]);
        }
        return $stmt;
    }

    /**
     * delete or truncate table under conditions
     *
     * @param string $table
     * @param string $where
     * @param array $data
     * @return bool|int
     */
    public function delete(string $table, string $where = '', array $data = [])
    {
        if (!isset($this->_tables[$table])) {
            throw new \PDOException("$table not found");
        }

        // if whole table, we do truncate instead of delete
        if (empty($where)) {
            return $this->exec("TRUNCATE TABLE $table");
        }

        $where = 'WHERE ' . $where;
        $valid_data = $this->getValidData($table, $data);
        $sql = "DELETE FROM $table $where";
        $stmt = $this->prepare($sql);
        if (!$stmt instanceof \PDOStatement) {
            throw new \PDOException($this->errorInfo()[2]);
        }

        $ret = $stmt->execute($valid_data);
        if (!$ret) {
            throw new \PDOException($stmt->errorInfo()[2]);
        }
        return $ret;
    }

    /**
     * insert data into table
     *
     * @param string $table
     * @param $mixed case a) a sequelize array of associative array; b) a callback for returning such data
     * @param bool $ignore
     * @return bool|string true/false for the sequelize array has more than 1 element, if only 1 element, it would returns last insert id
     */
    public function insert(string $table, $mixed, bool $ignore = false)
    {
        if (!isset($this->_tables[$table])) {
            throw new \PDOException("$table not found");
        }

        $ignore = $ignore ? 'IGNORE ' : '';
        if (is_array($mixed)) {
            if (empty($mixed) || empty($mixed[0])) throw new \PDOException("empty mixed");

            $valid_data = $this->getValidData($table, $mixed[0], $placeholders);
            $sql = sprintf("INSERT {$ignore}INTO $table (%s) VALUES (%s)", implode(',', array_keys($valid_data)), implode(',', $placeholders));
            $stmt = $this->prepare($sql);
            if (!$stmt instanceof \PDOStatement) {
                throw new \PDOException($this->errorInfo()[2]);
            }
            $stmt->execute($valid_data);

            $n = count($mixed);
            do {
                $n--;
                if (empty($mixed[$n])) { // skip
                    continue;
                }
                $valid_data = $this->getValidData($table, $mixed[$n]);
                $stmt->execute($valid_data);
            } while ($n > 1);

            return count($mixed) === 1 && ($pk = $this->_tables[$table]['pk']) ? $this->lastInsertId($pk) : true;
        } else if (is_callable($mixed)) {
            $init = false;
            $i = 0;

            foreach($mixed() as $item) {
                if (!$init) { // do initialize
                    $valid_data = $this->getValidData($table, $item, $placeholders);
                    $sql = sprintf("INSERT {$ignore}INTO $table (%s) VALUES (%s)", implode(',', array_keys($valid_data)), implode(',', $placeholders));
                    $stmt = $this->prepare($sql);
                    if (!$stmt instanceof \PDOStatement) {
                        throw new \PDOException($this->errorInfo()[2]);
                    }
                    $init = true;
                }

                $valid_data = $this->getValidData($table, $item);
                $stmt->execute($valid_data);
                $i++;
            }

            return $i == 1 && ($pk = $this->_tables[$table]['pk']) ? $this->lastInsertId($pk) : true;
        } else {
            return false;
        }
    }

    /**
     * fill the entity data into a model
     *
     * @param string $class
     * @param int|\PDOStatement $id
     * @param INameConvertStrategy|null $nameConvertStrategy
     * @return Model
     * @throws DmlException
     */
    public function fill(string $class, $id, INameConvertStrategy $nameConvertStrategy = null)
    {
        if (!class_exists($class)) {
            throw new DmlException("$class not found");
        }

        if ($nameConvertStrategy === null) {
            $nameConvertStrategy = NameConvertStrategy::GetDefault();
        }

        $table = $nameConvertStrategy->convert($class);
        if (!$id instanceof \PDOStatement) {
            $id = $this->get($table, intval($id));
        }

        return $id->fetchObject($class, [$table, ['IDml' => $this]]);
    }

    /**
     * @param \Closure $callback
     */
    public function transaction(\Closure $callback)
    {
        $this->beginTransaction();

        try {
            $callback();
            $this->commit();
        } catch (\PDOException $e) {
            $this->rollBack();
            throw $e;
        }
    }
}