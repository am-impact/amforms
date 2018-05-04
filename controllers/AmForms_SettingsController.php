<?php
namespace Craft;

/**
 * AmForms - Settings controller
 */
class AmForms_SettingsController extends BaseController
{
    /**
     * Make sure the current has access.
     */
    public function __construct()
    {
        $user = craft()->userSession->getUser();
        if (! $user->can('accessAmFormsSettings')) {
            throw new HttpException(403, Craft::t('This action may only be performed by users with the proper permissions.'));
        }
    }

    /**
     * Redirect index.
     */
    public function actionIndex()
    {
        $this->redirect('amforms/settings/general');
    }

    /**
     * Show settings.
     *
     * @param array $variables
     */
    public function actionShowSettings(array $variables = array())
    {
        // Do we have a settings type?
        if (! isset($variables['settingsType'])) {
            throw new Exception(Craft::t('Settings type is not set.'));
        }
        $settingsType = $variables['settingsType'];

        // Do we have any settings?
        $settings = craft()->amForms_settings->getSettingsByType($settingsType);
        if (! $settings) {
            throw new Exception(Craft::t('There are no settings available for settings type “{type}”.', array('type' => $settingsType)));
        }

        // Show settings!
        $variables['type'] = $settingsType;
        $variables[$settingsType] = $settings;
        $this->renderTemplate('amForms/settings/' . $settingsType, $variables);
    }

    /**
     * Saves settings.
     */
    public function actionSaveSettings()
    {
        $this->requirePostRequest();

        // Settings type
        $settingsType = craft()->request->getPost('settingsType', false);

        // Save settings!
        if ($settingsType) {
            $this->_saveSettings($settingsType);
        }
        else {
            craft()->userSession->setError(Craft::t('Couldn’t find settings type.'));
        }

        $this->redirectToPostedUrl();
    }

    /**
     * Save the settings for a specific type.
     *
     * @param string $type
     */
    private function _saveSettings($type)
    {
        $success = true;

        // Get all available settings for this type
        $availableSettings = craft()->amForms_settings->getSettingsByType($type);

        // Save each available setting
        foreach ($availableSettings as $setting) {
            // Find new settings
            $newSettings = craft()->request->getPost($setting->handle, false);

            if ($newSettings !== false) {
                $setting->value = $newSettings;
                if(! craft()->amForms_settings->saveSettings($setting)) {
                    $success = false;
                }
            }
        }

        // Save the settings in the plugins table
        $plugin = craft()->plugins->getPlugin('amForms');
        craft()->plugins->savePluginSettings($plugin, $plugin->getSettings());

        if ($success) {
            craft()->userSession->setNotice(Craft::t('Settings saved.'));
        }
        else {
            craft()->userSession->setError(Craft::t('Couldn’t save settings.'));
        }
    }
}
