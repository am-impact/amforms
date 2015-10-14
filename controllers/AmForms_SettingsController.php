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
     * Show General settings.
     */
    public function actionIndex()
    {
        $variables = array(
            'type'    => AmFormsModel::SettingGeneral,
            'general' => craft()->amForms_settings->getAllSettingsByType(AmFormsModel::SettingGeneral)
        );
        $this->renderTemplate('amForms/settings/index', $variables);
    }

    /**
     * Show Export settings.
     */
    public function actionExports()
    {
        $variables = array(
            'type'    => AmFormsModel::SettingExport,
            'exports' => craft()->amForms_settings->getAllSettingsByType(AmFormsModel::SettingExport)
        );
        $this->renderTemplate('amForms/settings/exports', $variables);
    }

    /**
     * Show AntiSpam settings.
     */
    public function actionAntispam()
    {
        $variables = array(
            'type'     => AmFormsModel::SettingAntispam,
            'antispam' => craft()->amForms_settings->getAllSettingsByType(AmFormsModel::SettingAntispam)
        );
        $this->renderTemplate('amForms/settings/antispam', $variables);
    }

    /**
     * Show reCAPTCHA settings.
     */
    public function actionRecaptcha()
    {
        $variables = array(
            'type'      => AmFormsModel::SettingRecaptcha,
            'recaptcha' => craft()->amForms_settings->getAllSettingsByType(AmFormsModel::SettingRecaptcha)
        );
        $this->renderTemplate('amForms/settings/recaptcha', $variables);
    }

    /**
     * Show Templates settings.
     */
    public function actionTemplates()
    {
        $variables = array(
            'type'      => AmFormsModel::SettingsTemplatePaths,
            'templates' => craft()->amForms_settings->getAllSettingsByType(AmFormsModel::SettingsTemplatePaths)
        );
        $this->renderTemplate('amForms/settings/templates', $variables);
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
        $availableSettings = craft()->amForms_settings->getAllSettingsByType($type);

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
