<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CorePluginsAdmin;
use Piwik\Piwik;
use Piwik\Plugin\SettingsProvider;
use Piwik\Settings\Plugin\SystemSetting;
use Exception;
use Piwik\Settings\Plugin\UserSetting;

/**
 * API for plugin CorePluginsAdmin
 *
 * @method static \Piwik\Plugins\CorePluginsAdmin\API getInstance()
 */
class API extends \Piwik\Plugin\API
{
    /**
     * @var SettingsMetadata
     */
    private $settingsMetadata;

    /**
     * @var SettingsProvider
     */
    private $settingsProvider;

    public function __construct(SettingsProvider $settingsProvider, SettingsMetadata $settingsMetadata)
    {
        $this->settingsProvider = $settingsProvider;
        $this->settingsMetadata = $settingsMetadata;
    }

    public function setSystemSettings($settingValues)
    {
        Piwik::checkUserHasSuperUserAccess();

        $pluginsSettings = $this->settingsProvider->getAllSystemSettings();

        $this->settingsMetadata->setPluginSettings($pluginsSettings, $settingValues);

        try {
            foreach ($pluginsSettings as $pluginSetting) {
                $pluginSetting->save();
            }
        } catch (Exception $e) {
            throw new Exception(Piwik::translate('CoreAdminHome_PluginSettingsSaveFailed'));
        }
    }

    public function setUserSettings($settingValues)
    {
        Piwik::checkUserIsNotAnonymous();

        $pluginsSettings = $this->settingsProvider->getAllUserSettings();

        $this->settingsMetadata->setPluginSettings($pluginsSettings, $settingValues);

        try {
            foreach ($pluginsSettings as $pluginSetting) {
                $pluginSetting->save();
            }
        } catch (Exception $e) {
            throw new Exception(Piwik::translate('CoreAdminHome_PluginSettingsSaveFailed'));
        }
    }

    public function getSystemSettings()
    {
        Piwik::checkUserHasSuperUserAccess();

        $systemSettings = $this->settingsProvider->getAllSystemSettings();

        return $this->settingsMetadata->formatSettings($systemSettings);
    }

    public function getUserSettings()
    {
        Piwik::checkUserIsNotAnonymous();

        $userSettings = $this->settingsProvider->getAllUserSettings();

        return $this->settingsMetadata->formatSettings($userSettings);
    }

}
