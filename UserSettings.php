<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\SharpSpringWidgetByAmperage;

use Piwik\Settings\Setting;
use Piwik\Settings\FieldConfig;

/**
 * Defines Settings for SharpSpringWidgetByAmperage.
 *
 * Usage like this:
 * $settings = new UserSettings();
 * $settings->autoRefresh->getValue();
 * $settings->color->getValue();
 */
class UserSettings extends \Piwik\Settings\Plugin\UserSettings
{
    /** @var Setting */
    public $sharpSpringAPIKey;

    /** @var Setting */
    public $sharpSpringSecretKey;

    protected function init()
    {
        $this->sharpSpringAPIKey = $this->createSharpSpringAPIKey();
        $this->sharpSpringSecretKey = $this->createSharpSpringSecretKey();
    }

    private function createSharpSpringAPIKey()
    {
        return $this->makeSetting('sharpSpringAPIKey', $default = '', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
            $field->title = 'Account ID';
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
            $field->uiControlAttributes = array('size' => 3);
            $field->description = 'The `Account ID` SharpSpring provides on https://marketingautomation.services/settings/pubapi (your exact URL may be slightly different)';
        });
    }

    private function createSharpSpringSecretKey()
    {
        return $this->makeSetting('sharpSpringSecretKey', $default = '', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
            $field->title = 'Secret Key';
            $field->uiControl = FieldConfig::UI_CONTROL_PASSWORD;
            $field->uiControlAttributes = array('size' => 3);
            $field->description = 'The `Secret Key` SharpSpring provides on https://marketingautomation.services/settings/pubapi (your exact URL may be slightly different)';
        });
    }

}
