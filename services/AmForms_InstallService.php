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
        $this->_installAntiSpam();
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
            if (! isset($setting['name']) || ! isset($setting['value'])) {
                continue;
            }

            // Add new setting!
            $settingRecord = new AmForms_SettingRecord();
            $settingRecord->type = $settingType;
            $settingRecord->name = $setting['name'];
            $settingRecord->handle = str_replace(' ', '', lcfirst(ucwords(strtolower($setting['name']))));
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
                'name' => 'Plugin name',
                'value' => ''
            ),
            array(
                'name' => 'Quiet errors',
                'value' => false
            ),
            array(
                'name' => 'Fields per set',
                'value' => 8
            ),
            array(
                'name' => 'Use Mandrill for email',
                'value' => false
            )
        );
        $this->installSettings($settings, AmFormsModel::SettingGeneral);
    }

    /**
     * Install AntiSpam settings.
     */
    private function _installAntiSpam()
    {
        $settings = array(
            array(
                'name' => 'Honeypot enabled',
                'value' => true
            ),
            array(
                'name' => 'Honeypot name',
                'value' => 'beesknees'
            ),
            array(
                'name' => 'Time check enabled',
                'value' => true
            ),
            array(
                'name' => 'Minimum time in seconds',
                'value' => 3
            ),
            array(
                'name' => 'Duplicate check enabled',
                'value' => true
            ),
            array(
                'name' => 'Origin check enabled',
                'value' => true
            )
        );
        $this->installSettings($settings, AmFormsModel::SettingAntispam);
    }

    /**
     * Install reCAPTCHA settings.
     */
    private function _installRecaptcha()
    {
        $settings = array(
            array(
                'name' => 'Google reCAPTCHA enabled',
                'value' => false
            ),
            array(
                'name' => 'Site key',
                'value' => ''
            ),
            array(
                'name' => 'Secret key',
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
            ),
            array(
                'name' => Craft::t('Image'),
                'type' => 'Assets',
                'translatable' => false,
                'settings' => array(
                    'restrictFiles' => 1,
                    'allowedKinds' => array('image'),
                    'sources' => array('folder:1'),
                    'singleUploadLocationSource' => '1',
                    'defaultUploadLocationSource' => '1',
                    'limit' => 1
                )
            ),
            array(
                'name' => Craft::t('File'),
                'type' => 'Assets',
                'translatable' => false,
                'settings' => array(
                    'sources' => array('folder:1'),
                    'singleUploadLocationSource' => '1',
                    'defaultUploadLocationSource' => '1',
                    'limit' => 1
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