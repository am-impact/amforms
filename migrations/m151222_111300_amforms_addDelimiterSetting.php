<?php

namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName.
 */
class m151222_111300_amforms_addDelimiterSetting extends BaseMigration
{
    /**
     * Any migration code in here is wrapped inside of a transaction.
     *
     * @return bool
     */
    public function safeUp()
    {
        $settings = array(
            array(
                'name' => 'Delimiter',
                'value' => ';',
            ),
        );
        return craft()->amForms_install->installSettings($settings, AmFormsModel::SettingExport);
    }
}
