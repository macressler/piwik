<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Updates;

use Piwik\Common;
use Piwik\Db;
use Piwik\Updater;
use Piwik\Updates;
use Piwik\Updater\Migration\Factory as MigrationFactory;

/**
 */
class Updates_1_7_2_rc7 extends Updates
{
    /**
     * @var MigrationFactory
     */
    private $migration;

    public function __construct(MigrationFactory $factory)
    {
        $this->migration = $factory;
    }

    public function getMigrations(Updater $updater)
    {
        $sqls = array(
            $this->migration->db->addColumn('user_dashboard', 'name', 'VARCHAR( 100 ) NULL DEFAULT NULL', 'iddashboard')
        );

        $table = Common::prefixTable('user_dashboard');
        $dashboards = Db::fetchAll('SELECT * FROM `' . $table . '`');

        $updateQuery = 'UPDATE `' . $table . '` SET layout = ? WHERE iddashboard = ? AND login = ?';

        foreach ($dashboards as $dashboard) {
            $idDashboard = $dashboard['iddashboard'];
            $login = $dashboard['login'];
            $layout = $dashboard['layout'];
            $layout = html_entity_decode($layout);
            $layout = str_replace("\\\"", "\"", $layout);

            $sqls[] = $this->migration->db->boundSql($updateQuery, array($layout, $idDashboard, $login));
        }

        return $sqls;
    }

    public function doUpdate(Updater $updater)
    {
        try {
            $updater->executeMigrations(__FILE__, $this->getMigrations($updater));
        } catch (\Exception $e) {
        }
    }
}
