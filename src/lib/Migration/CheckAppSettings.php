<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Migration;

use OCA\Passwords\Helper\AppSettings\ServiceSettingsHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\HelperService;
use OCA\Passwords\Services\NotificationService;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Class CheckAppSettings
 *
 * @package OCA\Passwords\Migration
 */
class CheckAppSettings implements IRepairStep {

    const APP_BC_BREAK_VERSION          = '2020.1';
    const NEXTCLOUD_MIN_VERSION         = 17;
    const NEXTCLOUD_RECOMMENDED_VERSION = '17';
    const PHP_MIN_VERSION               = 70200;
    const PHP_RECOMMENDED_VERSION       = '7.3.0';

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var ServiceSettingsHelper
     */
    protected $serviceSettings;

    /**
     * @var NotificationService
     */
    protected $notifications;

    /**
     * CheckAppSettings constructor.
     *
     * @param ServiceSettingsHelper $serviceSettings
     * @param NotificationService   $notifications
     * @param ConfigurationService  $config
     */
    public function __construct(ServiceSettingsHelper $serviceSettings, NotificationService $notifications, ConfigurationService $config) {
        $this->serviceSettings = $serviceSettings;
        $this->notifications   = $notifications;
        $this->config          = $config;
    }

    /**
     * Returns the step's name
     *
     * @return string
     * @since 9.1.0
     */
    public function getName() {
        return 'Check app settings';
    }

    /**
     * Run repair step.
     * Must throw exception on error.
     *
     * @param IOutput $output
     *
     * @throws \Exception in case of failure
     * @since 9.1.0
     */
    public function run(IOutput $output) {
        $faviconSetting    = $this->serviceSettings->get('favicon');
        $faviconApiSetting = $this->serviceSettings->get('favicon.api');

        if($faviconSetting['value'] === HelperService::FAVICON_BESTICON) {
            if(empty($faviconApiSetting['value'])) {
                $this->sendEmptySettingNotification('favicon');
            } /*else if($faviconApiSetting['isDefault'] || $faviconApiSetting['value'] === $faviconApiSetting['default']) {
                $this->sendBesticonApiNotification();
            }*/
        }

        $previewSetting    = $this->serviceSettings->get('preview');
        $previewApiSetting = $this->serviceSettings->get('preview.api');
        if(empty($previewApiSetting['value']) && in_array($previewSetting['value'], $previewApiSetting['depends']['service.preview'])) {
            $this->sendEmptySettingNotification('preview');
        }

        $ncVersion = intval(explode('.', $this->config->getSystemValue('version'), 2)[0]);
        if($ncVersion < self::NEXTCLOUD_MIN_VERSION || PHP_VERSION_ID < self::PHP_MIN_VERSION) {
            $this->sendDeprecatedPlatformNotification();
        }
    }

    /**
     * @param string $setting
     */
    protected function sendEmptySettingNotification(string $setting): void {
        $adminGroup = \OC::$server->getGroupManager()->get('admin');
        foreach($adminGroup->getUsers() as $admin) {
            $this->notifications->sendEmptyRequiredSettingNotification($admin->getUID(), $setting);
        }
    }

    /**
     *
     */
    protected function sendBesticonApiNotification(): void {
        $adminGroup = \OC::$server->getGroupManager()->get('admin');
        foreach($adminGroup->getUsers() as $admin) {
            $this->notifications->sendBesticonApiNotification($admin->getUID());
        }
    }

    /**
     *
     */
    protected function sendDeprecatedPlatformNotification(): void {
        $adminGroup = \OC::$server->getGroupManager()->get('admin');
        foreach($adminGroup->getUsers() as $admin) {
            $this->notifications->sendUpgradeRequiredNotification(
                $admin->getUID(),
                self::APP_BC_BREAK_VERSION,
                self::NEXTCLOUD_RECOMMENDED_VERSION,
                self::PHP_RECOMMENDED_VERSION
            );
        }
    }
}