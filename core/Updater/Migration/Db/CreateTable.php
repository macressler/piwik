<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Updater\Migration\Db;

use Piwik\Db;

/**
 * @see Factory::createTable()
 */
class CreateTable extends Sql
{
    /**
     * Constructor.
     * @param Db\Settings $dbSettings
     * @param string $table Unprefixed table name
     * @param array $columnNames array(columnName => columnValue)
     */
    public function __construct(Db\Settings $dbSettings, $table, $columnNames)
    {
        $columns = array();
        foreach ($columnNames as $column => $type) {
            $columns[] = sprintf('`%s` %s', $column, $type);
        }

        $sql = sprintf('CREATE TABLE `%s` (%s) ENGINE=%s DEFAULT CHARSET=utf8',
                       $table, implode(', ' , $columns), $dbSettings->getEngine());

        parent::__construct($sql, static::ERROR_CODE_TABLE_EXISTS);
    }

}
