<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Updater\Migration\Db;

use Piwik\Common;
use Piwik\Container\StaticContainer;

/**
 * Provides database migrations.
 *
 * @api
 */
class Factory
{
    /**
     * @var \DI\Container
     */
    private $container;

    /**
     * @ignore
     */
    public function __construct()
    {
        $this->container = StaticContainer::getContainer();
    }

    /**
     * Performs a custom SQL query during the update.
     *
     * Example:
     * $factory->sql("DELETE * FROM table_name WHERE plugin_name = 'MyPluginName'");
     *
     * @param string $sql  The SQL query that should be executed. Make sure to prefix a table name via
     *                     {@link Piwik\Commin::prefixTable()}.
     * @param int|int[]    $errorCodesToIgnore Any given MySQL server error code will be ignored. For a list of all
     *                                         possible error codes have a look at {@link \Piwik\Updater\Migration\Db}.
     *                                         If no error should be ignored use an empty array or `false`.
     * @return Sql
     */
    public function sql($sql, $errorCodesToIgnore = array())
    {
        if ($errorCodesToIgnore === false) {
            $errorCodesToIgnore = array();
        }

        return $this->container->make('Piwik\Updater\Migration\Db\Sql', array(
            'sql' => $sql, 'errorCodesToIgnore' => $errorCodesToIgnore
        ));
    }

    /**
     * Performs a custom SQL query that uses bound parameters during the update.
     *
     * You can replace values with a question mark and then pass the actual value via `$bind` for better security.
     *
     * Example:
     * $factory->boundSql('DELETE * FROM table_name WHERE idsite = ?, array($idSite = 1));
     *
     * @param string $sql  The SQL query that should be executed. Make sure to prefix a table name via
     *                     {@link Piwik\Commin::prefixTable()}.
     * @param array $bind  An array of values that need to be replaced with the question marks in the SQL query.
     * @param false|int|int[] $errorCodesToIgnore Any given MySQL server error code will be ignored. For a list of all
     *                                            possible error codes have a look at {@link \Piwik\Updater\Migration\Db}.
     *                                            If no error should be ignored use `false`.
     * @return BoundSql
     */
    public function boundSql($sql, $bind, $errorCodesToIgnore = false)
    {
        return $this->container->make('Piwik\Updater\Migration\Db\BoundSql', array(
            'sql' => $sql, 'errorCodesToIgnore' => $errorCodesToIgnore, 'bind' => $bind
        ));
    }

    /**
     * Creates a new database table.
     * @param string $table  Unprefixed database table name, eg 'log_visit'.
     * @param array $columnNames  An array of column names and their type they should use. For example:
     *                            array('column_name_1' => 'VARCHAR(200) NOT NULL', 'column_name_2' => 'INT(10) DEFAULT 0')
     * @return CreateTable
     */
    public function createTable($table, $columnNames)
    {
        $table = $this->prefixTable($table);

        return $this->container->make('Piwik\Updater\Migration\Db\CreateTable', array(
            'table' => $table, 'columnNames' => $columnNames
        ));
    }

    /**
     * Drops an existing database table.
     * @param string $table  Unprefixed database table name, eg 'log_visit'.
     * @return DropTable
     */
    public function dropTable($table)
    {
        $table = $this->prefixTable($table);

        return $this->container->make('Piwik\Updater\Migration\Db\DropTable', array(
            'table' => $table
        ));
    }

    /**
     * Adds a new database table column to an existing table.
     *
     * @param string $table  Unprefixed database table name, eg 'log_visit'.
     * @param string $columnName  The name of the column that shall be added, eg 'my_column_name'.
     * @param string $columnType  The column type it should have, eg 'VARCHAR(200) NOT NULL'.
     * @param string|null $placeColumnAfter  If specified, the added column will be added after this specified column
     *                                       name. If you specify a column be sure it actually exists and can be added
     *                                       after this column.
     * @return AddColumn
     */
    public function addColumn($table, $columnName, $columnType, $placeColumnAfter = null)
    {
        $table = $this->prefixTable($table);

        return $this->container->make('Piwik\Updater\Migration\Db\AddColumn', array(
            'table' => $table, 'columnName' => $columnName, 'columnType' => $columnType, 'placeColumnAfter' => $placeColumnAfter
        ));
    }

    /**
     * Changes the type of an existing database table column.
     *
     * @param string $table  Unprefixed database table name, eg 'log_visit'.
     * @param string $columnName  The name of the column that shall be changed, eg 'my_column_name'.
     * @param string $columnType  The updated type the column should have, eg 'VARCHAR(200) NOT NULL'.
     *
     * @return ChangeColumnType
     */
    public function changeColumnType($table, $columnName, $columnType)
    {
        $table = $this->prefixTable($table);

        return $this->container->make('Piwik\Updater\Migration\Db\ChangeColumnType', array(
            'table' => $table, 'columnName' => $columnName, 'columnType' => $columnType
        ));
    }

    /**
     * Adds an index to an existing database table.
     *
     * This is equivalent to an `ADD INDEX(column_name_1, column_name_2)` in SQL.
     * It adds a normal index, no unique index.
     *
     * @param string $table  Unprefixed database table name, eg 'log_visit'.
     * @param string[]|string $columnNames Either one or multiple column names, eg array('column_name_1', 'column_name_2')
     * @return AddIndex
     */
    public function addIndex($table, $columnNames)
    {
        $table = $this->prefixTable($table);
        if (!is_array($columnNames)) {
            $columnNames = array($columnNames);
        }

        return $this->container->make('Piwik\Updater\Migration\Db\AddIndex', array(
            'table' => $table, 'columnNames' => $columnNames
        ));
    }

    /**
     * Inserts a new record / row into an existing database table.
     *
     * Make sure to specify all columns that need to be defined in order to insert a value successfully. There could
     * be for example columns that are not nullable and therefore need a value.
     *
     * @param string $table  Unprefixed database table name, eg 'log_visit'.
     * @param array $columnValuePairs An array containing column => value pairs. For example:
     *                                array('column_name_1' => 'value1', 'column_name_2' => 'value2')
     * @return Insert
     */
    public function insert($table, $columnValuePairs)
    {
        $table = $this->prefixTable($table);

        return $this->container->make('Piwik\Updater\Migration\Db\Insert', array(
            'table' => $table, 'columnValuePairs' => $columnValuePairs
        ));
    }

    /**
     * Performs a batch insert into a specific table using either LOAD DATA INFILE or plain INSERTs,
     * as a fallback. On MySQL, LOAD DATA INFILE is 20x faster than a series of plain INSERTs.
     *
     * Please note that queries for batch inserts are currently not shown to an end user and should therefore not be
     * returned in an `Updates::getMigrations` method. Instead it needs to be execute directly in `Updates::doUpdate`
     * via `$updater->executeMigration($factory->dbBatchInsert(...));`
     *
     * @param string $table  Unprefixed database table name, eg 'log_visit'.
     * @param string[] $columnNames An array of unquoted column names, eg array('column_name1', 'column_name_2')
     * @param array $values An array of data to be inserted, eg array(array('row1column1', 'row1column2'),array('row2column1', 'row2column2'))
     * @param bool $throwException Whether to throw an exception that was caught while trying LOAD DATA INFILE, or not.
     * @param string $charset The charset to use, defaults to utf8
     * @return BatchInsert
     */
    public function batchInsert($table, $columnNames, $values, $throwException = false, $charset = 'utf8')
    {
        $table = $this->prefixTable($table);

        return $this->container->make('Piwik\Updater\Migration\Db\BatchInsert', array(
            'table' => $table, 'columnNames' => $columnNames, 'values' => $values,
            'throwException' => $throwException, 'charset' => $charset
        ));
    }

    private function prefixTable($table)
    {
        return Common::prefixTable($table);
    }
}
