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
        $this->_createContentTable();
        $this->_installGeneral();
        $this->_installExport();
        $this->_installAntiSpam();
        $this->_installRecaptcha();
        $this->_installTemplates();
        $this->_installFields();
    }

    /**
     * Create a set of settings.
     *
     * @param array  $settings
     * @param string $settingType
     */
    public function installSettings(array $settings, $settingType)
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
            $settingRecord->handle = $this->_camelCase($setting['name']);
            $settingRecord->value = $setting['value'];
            $settingRecord->save();
        }
        return true;
    }

    /**
     * Remove a set of settings.
     *
     * @param array  $settings
     * @param string $settingType
     * @return bool
     */
    public function removeSettings(array $settings, $settingType)
    {
        // Make sure we have proper settings
        if (! is_array($settings)) {
            return false;
        }

        // Remove settings
        foreach ($settings as $settingName) {
            $setting = craft()->amForms_settings->getSettingsByHandleAndType($this->_camelCase($settingName), $settingType);
            if ($setting) {
                craft()->amForms_settings->deleteSettingById($setting->id);
            }
        }
        return true;
    }

    /**
     * Create content table.
     */
    private function _createContentTable()
    {
        craft()->db->createCommand()->createTable('amforms_content', array(
            'elementId' => array('column' => ColumnType::Int, 'null' => false),
            'locale'    => array('column' => ColumnType::Locale, 'null' => false),
            'title'     => array('column' => ColumnType::Varchar),
        ));
        craft()->db->createCommand()->createIndex('amforms_content', 'elementId,locale', true);
        craft()->db->createCommand()->createIndex('amforms_content', 'title');
        craft()->db->createCommand()->addForeignKey('amforms_content', 'elementId', 'elements', 'id', 'CASCADE', null);
        craft()->db->createCommand()->addForeignKey('amforms_content', 'locale', 'locales', 'locale', 'CASCADE', 'CASCADE');
    }

    /**
     * Install General settings.
     */
    private function _installGeneral()
    {
        $settings = craft()->config->get('general', 'amforms');
        $this->installSettings($settings, AmFormsModel::SettingGeneral);
    }

    /**
     * Install Export settings.
     */
    private function _installExport()
    {
        $settings = craft()->config->get('export', 'amforms');
        $this->installSettings($settings, AmFormsModel::SettingExport);
    }

    /**
     * Install AntiSpam settings.
     */
    private function _installAntiSpam()
    {
        $settings = craft()->config->get('antispam', 'amforms');
        $this->installSettings($settings, AmFormsModel::SettingAntispam);
    }

    /**
     * Install reCAPTCHA settings.
     */
    private function _installRecaptcha()
    {
        $settings = craft()->config->get('recaptcha', 'amforms');
        $this->installSettings($settings, AmFormsModel::SettingRecaptcha);
    }

    /**
     * Install global templates settings.
     */
    private function _installTemplates()
    {
        $settings = craft()->config->get('templates', 'amforms');
        $this->installSettings($settings, AmFormsModel::SettingsTemplatePaths);
    }

    /**
     * Install Craft fields.
     */
    private function _installFields()
    {
        // Get fields to install
        $fields = craft()->config->get('fields', 'amforms');

        // Validate fields
        if (is_array($fields) && count($fields)) {

            // Set field context and content
            craft()->content->fieldContext = AmFormsModel::FieldContext;
            craft()->content->contentTable = AmFormsModel::FieldContent;

            // Create fields
            foreach ($fields as $field) {
                $newField = new FieldModel();
                $newField->name         = Craft::t($field['name']);
                $newField->handle       = isset($field['handle']) ? $field['handle'] : $this->_camelCase(Craft::t($field['name']));
                $newField->translatable = isset($field['translatable']) ? $field['translatable'] : true;
                $newField->type         = $field['type'];
                if (isset($field['instructions'])) {
                    $newField->instructions = $field['instructions'];
                }
                if (isset($field['settings'])) {
                    $newField->settings = $field['settings'];
                }
                craft()->fields->saveField($newField, false); // Don't validate
            }
        }
    }

    /**
     * Camel case a string.
     *
     * @param string $str
     *
     * @return string
     */
    private function _camelCase($str)
    {
        // Non-alpha and non-numeric characters become spaces
        $str = preg_replace('/[^a-z0-9]+/i', ' ', $str);

        // Camel case!
        return str_replace(' ', '', lcfirst(ucwords(strtolower(trim($str)))));
    }
}
