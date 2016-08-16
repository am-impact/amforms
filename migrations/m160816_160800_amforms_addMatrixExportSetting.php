<?php

namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName.
 */
class m160816_160800_amforms_addMatrixExportSetting extends BaseMigration
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
                'name' => 'Ignore Matrix multiple rows',
                'value' => false,
            ),
        );
        return craft()->amForms_install->installSettings($settings, AmFormsModel::SettingExport);
    }
}
