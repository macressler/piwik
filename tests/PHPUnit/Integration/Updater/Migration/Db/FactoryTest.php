<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Tests\Integration\Updater\Migration\Db;

use Piwik\Common;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Piwik\Updater\Migration\Db\AddColumn;
use Piwik\Updater\Migration\Db\AddIndex;
use Piwik\Updater\Migration\Db\BatchInsert;
use Piwik\Updater\Migration\Db\BoundSql;
use Piwik\Updater\Migration\Db\ChangeColumn;
use Piwik\Updater\Migration\Db\ChangeColumnType;
use Piwik\Updater\Migration\Db\CreateTable;
use Piwik\Updater\Migration\Db\DropTable;
use Piwik\Updater\Migration\Db\Factory;
use Piwik\Updater\Migration\Db\Insert;
use Piwik\Updater\Migration\Db\Sql;

/**
 * @group Core
 * @group Updater
 * @group Migration
 */
class FactoryTest extends IntegrationTestCase
{
    /**
     * @var Factory
     */
    private $factory;

    private $testTable = 'tablename';
    private $testTablePrefixed = '';

    public function setUp()
    {
        parent::setUp();
        
        $this->testTablePrefixed = Common::prefixTable($this->testTable);
        $this->factory = new Factory();
    }

    public function test_sql_returnsSqlInstance()
    {
        $migration = $this->sql();

        $this->assertTrue($migration instanceof Sql);
    }

    public function test_sql_forwardsQueryAndErrorCode()
    {
        $migration = $this->sql();

        $this->assertSame('SELECT 1;', '' . $migration);
        $this->assertSame(array(5), $migration->getErrorCodesToIgnore());
    }

    public function test_boundSql_returnsSqlInstance()
    {
        $migration = $this->boundSql();

        $this->assertTrue($migration instanceof BoundSql);
    }

    public function test_boundSql_forwardsParameters()
    {
        $migration = $this->boundSql();

        $this->assertSame("SELECT 2 WHERE 'query';", '' . $migration);
        $this->assertSame(array(8), $migration->getErrorCodesToIgnore());
    }

    public function test_createTable_returnsCreateTableInstance()
    {
        $migration = $this->createTable();

        $this->assertTrue($migration instanceof CreateTable);
    }

    public function test_createTable_forwardsParameters()
    {
        $migration = $this->createTable();

        $table = $this->testTablePrefixed;
        $this->assertSame("CREATE TABLE `$table` (`column` INT(10) DEFAULT 0, `column2` VARCHAR(255)) ENGINE=InnoDB DEFAULT CHARSET=utf8;", ''. $migration);
    }

    public function test_dropTable_returnsDropTableInstance()
    {
        $migration = $this->factory->dropTable($this->testTable);

        $this->assertTrue($migration instanceof DropTable);
    }

    public function test_dropTable_forwardsParameters()
    {
        $migration = $this->factory->dropTable($this->testTable);

        $table = $this->testTablePrefixed;
        $this->assertSame("DROP TABLE `$table`;", ''. $migration);
    }

    public function test_addColumn_returnsAddColumnInstance()
    {
        $migration = $this->addColumn();

        $this->assertTrue($migration instanceof AddColumn);
    }

    public function test_addColumn_forwardsParameters_withLastColumn()
    {
        $migration = $this->addColumn();

        $table = $this->testTablePrefixed;
        $this->assertSame("ALTER TABLE `$table` ADD COLUMN `column` INT(10) DEFAULT 0 AFTER `lastcolumn`;", '' . $migration);
    }

    public function test_changeColumnType_returnsChangeColumnTypeInstance()
    {
        $migration = $this->changeColumnType();

        $this->assertTrue($migration instanceof ChangeColumnType);
    }

    public function test_changeColumnType_forwardsParameters()
    {
        $migration = $this->changeColumnType();

        $table = $this->testTablePrefixed;
        $this->assertSame("ALTER TABLE `$table` CHANGE `column` `column` INT(10) DEFAULT 0;", '' . $migration);
    }

    public function test_addIndex_returnsAddIndexInstance()
    {
        $migration = $this->addIndex();

        $this->assertTrue($migration instanceof AddIndex);
    }

    public function test_addIndex_forwardsParameters()
    {
        $migration = $this->addIndex();

        $table = $this->testTablePrefixed;
        $this->assertSame("ALTER TABLE `$table` ADD INDEX(`column1`, `column3(10)`);", '' . $migration);
    }

    public function test_insert_returnsInsertInstance()
    {
        $migration = $this->insert();

        $this->assertTrue($migration instanceof Insert);
    }

    public function test_insert_forwardsParameters()
    {
        $migration = $this->insert();

        $table = $this->testTablePrefixed;
        $this->assertSame("INSERT INTO `$table` (`column1`, `column3`) VALUES ('val1',5);", ''. $migration);
    }

    public function test_batchInsert_returnsBatchInsertInstance()
    {
        $migration = $this->batchInsert();
        $this->assertTrue($migration instanceof BatchInsert);
    }

    public function test_batchInsert_forwardsParameters()
    {
        $migration = $this->batchInsert();
        $this->assertSame('<batch insert>', '' . $migration);
        $this->assertSame($this->testTablePrefixed, $migration->getTable());
        $this->assertSame(array('col1'), $migration->getColumnNames());
        $this->assertSame(array(array('val1')), $migration->getValues());
        $this->assertSame('utf8', $migration->getCharset());
        $this->assertTrue($migration->doesThrowException());
    }

    private function sql()
    {
        return $this->factory->sql('SELECT 1;', 5);
    }

    private function boundSql()
    {
        return $this->factory->boundSql('SELECT 2 WHERE ?;', array('column' => 'query'), array(8));
    }

    private function createTable()
    {
        return $this->factory->createTable($this->testTable, array('column' => 'INT(10) DEFAULT 0', 'column2' => 'VARCHAR(255)'));
    }

    private function addColumn()
    {
        return $this->factory->addColumn($this->testTable, 'column', 'INT(10) DEFAULT 0', 'lastcolumn');
    }

    private function changeColumnType()
    {
        return $this->factory->changeColumnType($this->testTable, 'column', 'INT(10) DEFAULT 0');
    }

    private function addIndex()
    {
        return $this->factory->addIndex($this->testTable, array('column1', 'column3(10)'));
    }

    private function insert()
    {
        return $this->factory->insert($this->testTable, array('column1' => 'val1', 'column3' => 5));
    }

    private function batchInsert()
    {
        $columns = array('col1');
        $values = array(array('val1'));
        return $this->factory->batchInsert($this->testTable, $columns, $values, $throwException = true, $charset = 'utf8');
    }


}
