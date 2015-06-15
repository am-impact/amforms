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
        if ($this->_isQuietErrorsEnabled()) {
            AmFormsPlugin::log('Error::', $e->getMessage(), LogLevel::Warning);
        }
        else {
            throw $e;
        }
    }

    /**
     * Check whether to log errors or throw them.
     *
     * @return bool
     */
    private function _isQuietErrorsEnabled()
    {
        $quietErrors = craft()->amForms_settings->getSettingsByHandleAndType('quietErrors', AmFormsModel::SettingGeneral);
        if (is_null($quietErrors)) {
            return false;
        }
        return $quietErrors->value;
    }
}