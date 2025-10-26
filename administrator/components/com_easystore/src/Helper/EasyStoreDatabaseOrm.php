<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Helper;

use Exception;
use Throwable;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * EasyStore database ORM class.
 * This class contains some basic functionalities of managing
 * one-to-one, one-to-many, and many-to-many relationships.
 */
class EasyStoreDatabaseOrm
{
    private $db             = null;
    public $query           = null;
    private $columns        = [];
    private $isRawColumns   = false;
    public $referenceTable  = null;
    public $foreignKey      = null;
    public $pivotTable      = null;
    public $referenceKey    = null;

    public function __construct()
    {
        $this->db      = Factory::getContainer()->get(DatabaseInterface::class);
        $this->query   = $this->db->getQuery(true);
        $this->columns = [];
    }

    /**
     * Read the columns from the user and set.
     *
     * @param   array $columns  The columns array.
     *
     * @return  self
     */
    public function setColumns(array $columns)
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * If the status is true then we are going to use the raw columns set by setColumns method.
     *
     * @param   bool $status
     *
     * @return  self
     */
    public function useRawColumns(bool $status)
    {
        $this->isRawColumns = $status;

        return $this;
    }

    /**
     * Get the columns. If there is no specific column is set earlier
     * then it returns * (means all columns).
     * It also adds a prefix with the columns if passed.
     *
     * @param   string  $prefix
     *
     * @return  void
     */
    private function getColumns(?string $prefix = null)
    {
        if ($this->isRawColumns) {
            return $this->columns;
        }

        if (empty($this->columns)) {
            return $prefix ? $prefix . '.*' : '*';
        }

        return
            $this->db->quoteName(array_map(function ($column) use ($prefix) {
                return !empty($prefix) ? $prefix . '.' . $column : $column;
            }, $this->columns));
    }

    /**
     * Modify the $db->quoteName() function and add a prefix if passed.
     *
     * @param   string  $name   The column name.
     * @param   string  $prefix The prefix the add.
     *
     * @return  void
     */
    private function quoteNameWithPrefix(string $name, string $prefix)
    {
        return $this->db->quoteName($prefix . '.' . $name);
    }

    /**
     * Allow others to use the `quoteName` function by using this class instance.
     *
     * @param   string  $name
     *
     * @return  string
     */
    public function quoteName(string $name)
    {
        return $this->db->quoteName($name);
    }

    /**
     * Allow others to use the `quote` function by using this class instance.
     *
     * @param   mixed   $value
     *
     * @return  void
     */
    public function quote($value)
    {
        return $this->db->quote($value);
    }

    /**
     * Quote name with aggregate functions like MAX, MIN, COUNT etc.
     *
     * @param string        $fn         The aggregate function name.
     * @param string        $column     The column name
     * @param string|null   $as         If any rename exists
     *
     * @return string       The quote named column.
     */
    public function aggregateQuoteName(string $fn, string $column, ?string $as = null)
    {
        $quote = $fn . '(' . $this->db->quoteName($column) . ')';

        if (!empty($as)) {
            $quote .= ' AS ' . $this->db->quoteName($as);
        }

        return $quote;
    }

    /**
     * Add where clause to the reference table.
     *
     * @param   string  $query  The where clause query string.
     *
     * @return  self
     */
    public function whereInReference(string $query)
    {
        $this->query->where($query);

        return $this;
    }

    /**
     * Update the $this->query from out of the box.
     *
     * @param   callable    $queryFn
     *
     * @return  self
     */
    public function updateQuery(callable $queryFn)
    {
        call_user_func_array($queryFn, [$this->query]);

        return $this;
    }

    /**
     * Get data from any table
     *
     * @param   string  $tableName  The table name.
     * @param   string  $key        The key value for where clause.
     * @param   mixed   $value      The value to check with the where clause's key.
     * @param   mixed   $select     The value to check with the where clause's key can be selected columns with array.
     * @param   array   $extraWhere Array of object for extra conditions
     *
     * @return  object
     */
    public static function get(string $tableName, $key, $value, $select = '*', $extraWhere = [])
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $value = is_numeric($value) ? $value : $db->quote($value);

        if (is_array($select)) {
            foreach ($select as &$column) {
                if (strstr($column, '.')) {
                    list($col, $alias)  = explode('.', $column);
                    $column             = $db->quoteName($col, $alias);
                } else {
                    $column = $db->quoteName($column);
                }
            }

            unset($column);
        } else {
            $select = $select !== '*' ? $db->quoteName($select) : $select;
        }

        $query->select($select)
            ->from($db->quoteName($tableName));
        if (!empty($key)) {
            $query->where($db->quoteName($key) . ' = ' . $value);
        }


        if (!empty($extraWhere)) {
            foreach ($extraWhere as $where) {
                $query->where($db->quoteName($where->key) . ' ' . $where->operator . ' ' . $db->quote($where->value));
            }
        }

        $db->setQuery($query);

        return $db;
    }

    /**
     * Established the hasOne relationship.
     * This relationship function is responsible for getting data from a one-to-one relationship.
     *
     * @param   mixed   $id                 The foreign table's ID by which the we get the result from the reference table.
     * @param   string  $referenceTable     The reference table name.
     * @param   string  $foreignKey         The foreign key situated on the reference table.
     *
     * @return  object
     */
    public function hasOne($id, string $referenceTable, string $foreignKey)
    {
        $this->referenceTable = $referenceTable;
        $this->foreignKey     = $foreignKey;

        $this->query->select($this->getColumns())
            ->from($this->db->quoteName($referenceTable))
            ->where($this->db->quoteName($foreignKey) . ' = ' . $this->db->quote($id));

        return $this;
    }

    /**
     * Establishing hasMany relationship.
     * This relationship function is responsible for retrieving list of data from a one-to-many relationship.
     *
     * @param   mixed       $id                 The foreign table's ID, by which we get the result from the reference table.
     * @param   string      $referenceTable     The reference table name.
     * @param   string      $foreignKey         The foreign key exists on the reference table.
     *
     * @return  self
     */
    public function hasMany($id, $referenceTable, string $foreignKey)
    {
        $this->referenceTable = $referenceTable;
        $this->foreignKey     = $foreignKey;

        if (is_array($referenceTable) && count($referenceTable) > 1) {
            $tableName = $referenceTable[0];
            $as        = $referenceTable[1];
        } else {
            $tableName = $referenceTable;
            $as        = '';
        }

        if ($as) {
            $foreignKey = $this->quoteNameWithPrefix($foreignKey, $as);
        }

        $this->query->select($this->getColumns($as))
            ->from($this->db->quoteName($tableName, $as))
            ->where($foreignKey . ' = ' . $this->db->quote($id));

        return $this;
    }

    /**
     * Establishing belongsToMany relationship.
     * This function is mainly responsible for retrieving data from a pivot table and two other tables.
     * For many-to-many relationship, one table e.g. table-A could related to many entires of table-B,
     * and the table-B could related to many entries of table-A. In this situation we store the primary keys to
     * a pivot table and retrieve the data using the pivot table.
     *
     * If we want to get all the records of table-B which are related to the table-A, in that case,
     * we are calling table-A the foreign table and table-B is the reference table.
     *
     * @param   $id                 The foreign key's ID
     * @param   $referenceTable     The another table other than the foreign key's table.
     * @param   $pivotTable         The pivot table name.
     * @param   $foreignKey         The foreign key name from the pivot table.
     * @param   $referenceKey       The reference table key from the pivot table.
     */
    public function belongsToMany($id, string $referenceTable, string $pivotTable, string $foreignKey, string $referenceKey)
    {
        $this->referenceTable = $referenceTable;
        $this->pivotTable     = $pivotTable;
        $this->foreignKey     = $foreignKey;
        $this->referenceKey   = $referenceKey;

        $this->query->select($this->getColumns('r'))
            ->from($this->db->quoteName($referenceTable, 'r'))
            ->join('INNER', $this->db->quoteName($pivotTable, 'p') . ' ON(' . $this->quoteNameWithPrefix($referenceKey, 'p') . ' = ' . $this->quoteNameWithPrefix('id', 'r') . ')')
            ->where($this->quoteNameWithPrefix($foreignKey, 'p') . ' = ' . $this->db->quote($id));

        return $this;
    }

    public static function load(string $table, $value, $key = 'id')
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select('*')
            ->from($db->quoteName($table))
            ->where($db->quoteName($key) . ' = ' . $db->quote($value));
        $db->setQuery($query);

        try {
            return $db->loadObject() ?? null;
        } catch (Exception $error) {
            return null;
        }
    }

    public static function update(string $table, object &$data, string $pk = 'id')
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);

        if (empty($data->$pk)) {
            throw new Exception(sprintf('Missing %s field\'s value on your dataset', $pk));
        }

        $db->updateObject($table, $data, $pk, true);

        return $data;
    }

    public static function create(string $table, object $data, string $pk = 'id')
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        if (!empty($data->$pk)) {
            $data->$pk = null;
        }

        $db->insertObject($table, $data, $pk);

        return $data;
    }

    /**
     * Update or Create records.
     *
     * @param   string  $table  The table name.
     * @param   object  $data   The data object.
     * @param   string  $pk     The primary key.
     *
     * @return  object|bool
     */
    public static function updateOrCreate(string $table, object $data, string $pk = 'id')
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $record   = isset($data->$pk) ? static::load($table, $data->$pk, $pk) : null;
        $isUpdate = !is_null($record);

        if ($isUpdate) {
            $fields = [];

            foreach ($data as $key => $value) {
                if ($key === $pk || \is_null($value)) {
                    continue;
                }

                $fields[] = $db->quoteName($key) . ' = ' . $db->quote($value);
            }

            $conditions = [$db->quoteName($pk) . ' = ' . $db->quote($data->$pk)];
            $query->update($db->quoteName($table))
                ->set($fields)
                ->where($conditions);

            try {
                $db->setQuery($query);
                $db->execute();

                $result = (object) array_merge((array) $record, (array) $data);

                return $result;
            } catch (Throwable $error) {
                throw $error;
            }
        } else {
            $columns = [];
            $values  = [];

            foreach ($data as $key => $value) {
                if ($key === 'id' || \is_null($value)) {
                    continue;
                }

                $columns[] = $db->quoteName($key);
                $values[]  = $db->quote($value);
            }

            $query->insert($db->quoteName($table))
                ->columns($columns)
                ->values(implode(',', $values));

            try {
                $db->setQuery($query);
                $db->execute();

                $data->id = $db->insertid();

                return $data;
            } catch (Throwable $error) {
                throw $error;
            }
        }
    }

    /**
     * Remove from table by Ids
     *
     * @param string    $table          The table name
     * @param array     $ids            The Ids to delete
     * @param string    $pk             Column name for condition
     * @param array     $extraCondition Extra conditions in array.
     *
     * @return bool
     */
    public static function removeByIds(string $table, array $ids, string $pk = 'id', array $extraCondition = [])
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        if (empty($ids)) {
            return false;
        }

        $conditions = [$db->quoteName($pk) . ' IN (' . implode(',', $ids) . ')'];

        $query->delete($db->quoteName($table))
            ->where($conditions);

        if (!empty($extraCondition)) {
            foreach ($extraCondition as $condition) {
                $query->where($db->quoteName($condition['key']) . ' ' . $condition['operator'] . ' ' . $db->quote($condition['value']));
            }
        }

        $db->setQuery($query);

        try {
            $db->execute();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Store multiple records to a table
     *
     * @param string $table The table name
     * @param array $data   The data to store
     * @return bool
     */
    public static function insertMultipleRecords(string $table, array $data)
    {
        if (empty($data)) {
            return false;
        }

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $columns = array_map(function ($column) use ($db) {
            return $db->quoteName($column);
        }, array_keys($data[0]));

        $query->insert($db->quoteName($table))
            ->columns($columns);

        foreach ($data as $item) {
            $values = array_map(function ($value) use ($db) {
                return $db->quote($value);
            }, array_values($item));

            $query->values(implode(',', $values));
        }

        $db->setQuery($query);

        try {
            $db->execute();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function __call(string $method, $arguments)
    {
        $validLoadMethods = ['loadResult', 'loadObject', 'loadObjectList', 'loadColumn', 'loadAssocList', 'loadAssoc'];

        if (!\in_array($method, $validLoadMethods)) {
            throw new \BadMethodCallException(\sprintf('Invalid method %s!', $method));
        }

        try {
            $this->db->setQuery($this->query);
            $this->query->clear();
            $this->setColumns([]);
            $this->useRawColumns(false);

            return call_user_func([$this->db, $method]);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function __toString()
    {
        return $this->query->__toString();
    }
}