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
        $this->_installRecaptcha();
        $this->_installFields();
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
                'name' => 'Use Mandrill for email',
                'handle' => 'useMandrillForEmail',
                'value' => false
            )
        );
        $this->installSettings($settings, AmFormsModel::SettingGeneral);
    }

    /**
     * Install reCAPTCHA settings.
     */
    private function _installRecaptcha()
    {
        $settings = array(
            array(
                'name' => 'Use reCAPTCHA',
                'handle' => 'useRecaptcha',
                'value' => false
            ),
            array(
                'name' => 'Site key',
                'handle' => 'siteKey',
                'value' => ''
            ),
            array(
                'name' => 'Secret key',
                'handle' => 'secretKey',
                'value' => ''
            )
        );
        $this->installSettings($settings, AmFormsModel::SettingRecaptcha);
    }

    /**
     * Install Craft fields.
     */
    private function _installFields()
    {
        // Fields to install
        $fields = array(
            array(
                'name' => Craft::t('Name'),
                'type' => 'PlainText'
            ),
            array(
                'name' => Craft::t('First name'),
                'type' => 'PlainText'
            ),
            array(
                'name' => Craft::t('Last name'),
                'type' => 'PlainText'
            ),
            array(
                'name' => Craft::t('Website'),
                'type' => 'PlainText'
            ),
            array(
                'name' => Craft::t('Email address'),
                'type' => 'PlainText'
            ),
            array(
                'name' => Craft::t('Telephone number'),
                'type' => 'PlainText'
            ),
            array(
                'name' => Craft::t('Mobile number'),
                'type' => 'PlainText'
            ),
            array(
                'name' => Craft::t('Comment'),
                'type' => 'PlainText',
                'settings' => array(
                    'multiline'   => 1,
                    'initialRows' => 4
                )
            ),
            array(
                'name' => Craft::t('Reaction'),
                'type' => 'PlainText',
                'settings' => array(
                    'multiline'   => 1,
                    'initialRows' => 4
                )
            )
        );

        // Set field context
        craft()->content->fieldContext = AmFormsModel::FieldContext;

        // Create fields
        foreach ($fields as $field) {
            $newField = new FieldModel();
            $newField->name         = $field['name'];
            $newField->handle       = str_replace(' ', '', lcfirst(ucwords(strtolower($field['name']))));
            $newField->translatable = isset($field['translatable']) ? $field['translatable'] : true;
            $newField->type         = $field['type'];
            if (isset($field['instructions'])) {
                $newField->instructions = $field['instructions'];
            }
            if (isset($field['settings'])) {
                $newField->settings = $field['settings'];
            }
            craft()->fields->saveField($newField);
        }
    }
}