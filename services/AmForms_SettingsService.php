<?php
namespace Craft;

/**
 * AmForms - Settings service
 */
class AmForms_SettingsService extends BaseApplicationComponent
{
    /**
     * Get all settings by their type.
     *
     * @param string $type
     * @param bool   $enabled [Optional] Whether to include the enabled as search attribute.
     *
     * @return AmForms_SettingModel
     */
    public function getAllSettingsByType($type, $enabled = '*')
    {
        $attributes = array(
            'type' => $type
        );

        // Include enabled attribute?
        if ($enabled !== '*') {
            $attributes['enabled'] = $enabled;
        }

        // Find records
        $settingRecords = AmForms_SettingRecord::model()->ordered()->findAllByAttributes($attributes);
        if ($settingRecords) {
            return AmForms_SettingModel::populateModels($settingRecords, 'handle');
        }
        return null;
    }

    /**
     * Get all settings.
     *
     * @return array
     */
    public function getAllSettings()
    {
        $settingRecords = AmForms_SettingRecord::model()->ordered()->findAll();
        return AmForms_SettingModel::populateModels($settingRecords, 'handle');
    }

    /**
     * Get a setting by their handle and type.
     *
     * @param string $handle
     * @param string $type
     *
     * @return AmForms_SettingModel
     */
    public function getSettingsByHandleAndType($handle, $type)
    {
        $attributes = array(
            'type' => $type,
            'handle' => $handle
        );

        // Find record
        $settingRecord = AmForms_SettingRecord::model()->findByAttributes($attributes);
        if ($settingRecord) {
            return AmForms_SettingModel::populateModel($settingRecord);
        }
        return null;
    }

    /**
     * Get a setting value by their handle and type.
     *
     * @param string $handle
     * @param string $type
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    public function getSettingsValueByHandleAndType($handle, $type, $defaultValue)
    {
        $setting = $this->getSettingsByHandleAndType($handle, $type);
        if ($setting) {
            return $setting->value;
        }
        return $defaultValue;
    }

    /**
     * Check whether a setting value is enabled.
     * Note: Only for (booleans) light switches.
     *
     * @return bool
     */
    public function isSettingValueEnabled($handle, $type)
    {
        $setting = $this->getSettingsByHandleAndType($handle, $type);
        if (is_null($setting)) {
            return false;
        }
        return $setting->value;
    }

    /**
     * Save settings.
     *
     * @param AmForms_SettingModel
     *
     * @return bool
     */
    public function saveSettings(AmForms_SettingModel $settings)
    {
        if (! $settings->id) {
            return false;
        }

        $settingsRecord = AmForms_SettingRecord::model()->findById($settings->id);

        if (! $settingsRecord) {
            throw new Exception(Craft::t('No settings exists with the ID “{id}”.', array('id' => $settings->id)));
        }

        // Set attributes
        $properSettings = $settings->value;
        if (is_array($properSettings)) {
            $properSettings = json_encode($settings->value);
        }
        $settingsRecord->setAttributes($settings->getAttributes(), false);
        $settingsRecord->setAttribute('value', $properSettings);

        // Validate
        $settingsRecord->validate();
        $settings->addErrors($settingsRecord->getErrors());

        // Save settings
        if (! $settings->hasErrors()) {
            // Save in database
            return $settingsRecord->save();
        }
        return false;
    }

    /**
     * Delete a setting.
     *
     * @param int $id
     *
     * @return bool
     */
    public function deleteSettingById($id)
    {
        return craft()->db->createCommand()->delete('amforms_settings', array('id' => $id));
    }
}
