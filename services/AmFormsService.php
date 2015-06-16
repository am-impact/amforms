<?php
namespace Craft;

/**
 * AmForms service
 */
class AmFormsService extends BaseApplicationComponent
{
    /**
     * Handle an error message.
     *
     * @param string $message
     */
    public function handleError($message)
    {
        $e = new Exception($message);
        if (craft()->amForms_settings->isSettingValueEnabled('quietErrors', AmFormsModel::SettingGeneral)) {
            AmFormsPlugin::log('Error::', $e->getMessage(), LogLevel::Warning);
        }
        else {
            throw $e;
        }
    }
}