<?php
namespace Craft;

/**
 * AmForms - Install service
 */
class AmForms_InstallService extends BaseApplicationComponent
{
    /**
     * Install essential information.
     */
    public function install()
    {
        $this->_installGeneral();
    }

    /**
     * Create a set of settings.
     *
     * @param array  $settings
     * @param string $settingType
     */
    private function installSettings(array $settings, $settingType)
    {
        // Make sure we have proper settings
        if (! is_array($settings)) {
            return false;
        }

        // Add settings
        foreach ($settings as $setting) {
            // Only install if we have proper keys
            if (! isset($setting['name']) || ! isset($setting['handle']) || ! isset($setting['value'])) {
                continue;
            }

            // Add new setting!
            $settingRecord = new AmForms_SettingRecord();
            $settingRecord->type = $settingType;
            $settingRecord->name = $setting['name'];
            $settingRecord->handle = $setting['handle'];
            $settingRecord->value = $setting['value'];
            $settingRecord->save();
        }
    }

    /**
     * Install General settings.
     */
    private function _installGeneral()
    {
        $settings = array(
            array(
                'name' => 'Quiet errors',
                'handle' => 'quietErrors',
                'value' => false
            ),
            array(
                'name' => 'Store submissions',
                'handle' => 'storeSubmissions',
                'value' => true
            ),
            array(
                'name' => 'Use Mandrill for email',
                'handle' => 'useMandrillForEmail',
                'value' => false
            )
        );
        $this->installSettings($settings, AmFormsModel::SettingGeneral);
    }
}