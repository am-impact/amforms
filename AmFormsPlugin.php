<?php
/**
 * Forms for Craft.
 *
 * @package   Am Forms
 * @author    Hubert Prein
 */
namespace Craft;

class AmFormsPlugin extends BasePlugin
{
    public function getName()
    {
        $settings = $this->getSettings();
        if ($settings->pluginName) {
            return $settings->pluginName;
        }
        return Craft::t('a&m forms');
    }

    public function getVersion()
    {
        return '0.1';
    }

    public function getDeveloper()
    {
        return 'a&m impact';
    }

    public function getDeveloperUrl()
    {
        return 'http://www.am-impact.nl';
    }

    /**
     * Plugin has control panel section.
     *
     * @return boolean
     */
    public function hasCpSection()
    {
        return true;
    }

    /**
     * Plugin has Control Panel routes.
     *
     * @return array
     */
    public function registerCpRoutes()
    {
        return array(
            'amforms/fields' => array(
                'action' => 'amForms/fields/index'
            ),
            'amforms/fields/new' => array(
                'action' => 'amForms/fields/editField'
            ),
            'amforms/fields/edit/(?P<fieldId>\d+)' => array(
                'action' => 'amForms/fields/editField'
            ),

            'amforms/forms' => array(
                'action' => 'amForms/forms/index'
            ),
            'amforms/forms/new' => array(
                'action' => 'amForms/forms/editForm'
            ),
            'amforms/forms/edit/(?P<formId>\d+)' => array(
                'action' => 'amForms/forms/editForm'
            ),

            'amforms/submissions' => array(
                'action' => 'amForms/submissions/index'
            ),
            'amforms/submissions/edit/(?P<submissionId>\d+)' => array(
                'action' => 'amForms/submissions/editSubmission'
            ),

            'amforms/settings' => array(
                'action' => 'amForms/settings/index'
            ),
            'amforms/settings/recaptcha' => array(
                'action' => 'amForms/settings/recaptcha'
            )
        );
    }

    /**
     * Plugin has user permissions.
     *
     * @return array
     */
    public function registerUserPermissions()
    {
        return array(
            'editAmFormsSettings'   => array(
                'label' => Craft::t('Edit settings')
            )
        );
    }

    /**
     * Install essential information after installing the plugin.
     */
    public function onAfterInstall()
    {
        craft()->amForms_install->install();
    }

    public function getSettingsHtml()
    {
        return craft()->templates->render('amForms/settings', array(
            'settings' => $this->getSettings()
        ));
    }

    /**
     * Plugin settings.
     *
     * @return array
     */
    protected function defineSettings()
    {
        return array(
            'pluginName' => array(AttributeType::String)
        );
    }
}