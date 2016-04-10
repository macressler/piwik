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
class DropTable extends Sql
{
    /**
     * @param string $table Unprefixed table name
     */
    public function __construct($table)
    {
        $sql = sprintf('DROP TABLE `%s`', $table);

        parent::__construct($sql, array(static::ERROR_CODE_TABLE_NOT_EXISTS, static::ERROR_CODE_TABLE_UNKNOWN));
    }

}
