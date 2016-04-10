<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Updater\Migration\Db;

/**
 * @see Factory::changeColumn()
 */
class ChangeColumnType extends Sql
{
    public function __construct($table, $columnName, $columnType)
    {
        $sql = sprintf("ALTER TABLE `%s` CHANGE `%s` `%s` %s", $table, $columnName, $columnName, $columnType);
        parent::__construct($sql, static::ERROR_CODE_DUPLICATE_COLUMN);
    }

}
