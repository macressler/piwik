<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Tests\Integration\Updater\Migration\Db;

use Piwik\Common;
use Piwik\Db;
use Piwik\DbHelper;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Piwik\Updater\Migration\Db\Factory;

/**
 * Test where migrations are actually executed.
 *
 * @group Core
 * @group Updater
 * @group Migration
 * @group SqlTest
 */
class MigrationsTest extends IntegrationTestCase
{
    /**
     * @var Factory
     */
    private $factory;

    private $testTable = 'tablename';
    private $testTablePrefixed = '';

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::dropTestTableIfNeeded();
    }

    public static function tearDownAfterClass()
    {
        self::dropTestTableIfNeeded();

        parent::tearDownAfterClass();
    }

    private static function dropTestTableIfNeeded()
    {
        $table = Common::prefixTable('tablename');
        Db::exec("DROP TABLE IF EXISTS `$table`");
    }

    public function setUp()
    {
        parent::setUp();

        $this->testTablePrefixed = Common::prefixTable($this->testTable);
        $this->factory = new Factory();
    }

    public function test_createTable()
    {
        $columns = array('column1' => 'VARCHAR(200) DEFAULT ""', 'column2' => 'INT(11) NOT NULL');
        $this->factory->createTable($this->testTable, $columns)->exec();

        $this->assertTableIsInstalled();
        $this->assertSame(array('column1', 'column2'), $this->getInstalledColumnNames());
    }

    /**
     * @depends test_createTable
     */
    public function test_addColumn()
    {
        $this->factory->addColumn($this->testTable, 'column3', 'SMALLINT(1)')->exec();

        $this->assertSame(array('column1', 'column2', 'column3'), $this->getInstalledColumnNames());
    }

    /**
     * @depends test_addColumn
     */
    public function test_addIndex()
    {
        $this->factory->addIndex($this->testTable, array('column1', 'column3'))->exec();

        $index = Db::fetchAll("SHOW INDEX FROM {$this->testTablePrefixed}");

        $this->assertSame('column1', $index[0]['Column_name']);
        $this->assertSame('column1', $index[0]['Key_name']);
        $this->assertSame('column3', $index[1]['Column_name']);
        $this->assertSame('column1', $index[1]['Key_name']);
    }

    /**
     * @depends test_addIndex
     */
    public function test_changeColumnType()
    {
        $this->factory->changeColumnType($this->testTable, 'column2', 'SMALLINT(4) NOT NULL')->exec();
    }

    /**
     * @depends test_changeColumnType
     */
    public function test_insert()
    {
        $values = array(
            'column1' => 'my text',
            'column2' => '554934',
            'column3' => '1'
        );
        $this->factory->insert($this->testTable, $values)->exec();

        $row = Db::fetchRow("SELECT * FROM {$this->testTablePrefixed}");

        $values['column2'] = 32767; // because we changed type to smallint before
        $this->assertEquals($values, $row);
    }

    /**
     * @depends test_insert
     */
    public function test_sql()
    {
        $this->factory->sql("ALTER TABLE {$this->testTablePrefixed} CHANGE COLUMN `column2` `column5` SMALLINT(4) NOT NULL")->exec();

        $this->assertSame(array('column1', 'column5', 'column3'), $this->getInstalledColumnNames());
    }

    /**
     * @depends test_sql
     */
    public function test_dropTable()
    {
        $this->factory->dropTable($this->testTable)->exec();

        $this->assertTableIsNotInstalled();
    }

    private function assertTableIsInstalled()
    {
        $this->assertNotEmpty($this->getInstalledTable());
    }

    private function assertTableIsNotInstalled()
    {
        $this->assertEmpty($this->getInstalledTable());
    }

    private function getInstalledTable()
    {
        return Db::fetchAll("SHOW TABLES LIKE '{$this->testTablePrefixed}'");
    }

    private function getInstalledColumnNames()
    {
        $columns = DbHelper::getTableColumns($this->testTablePrefixed);
        return array_keys($columns);
    }
}
