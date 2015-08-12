<?php
namespace Craft;

class m150811_122900_amForms_settingsAndSubmissionsUpdate extends BaseMigration
{
    public function safeUp()
    {
        // Submissions update
        $this->addColumnAfter('amforms_submissions', 'submittedFrom', array(ColumnType::Varchar), 'userAgent');

        // Remove export setting from general settings
        $settings = array(
            'Export rows per set'
        );
        craft()->amForms_install->removeSettings($settings, AmFormsModel::SettingGeneral);

        // Install export settings
        $settings = array(
            array(
                'name' => 'Export rows per set',
                'value' => 50
            ),
            array(
                'name' => 'Ignore Matrix field and block names',
                'value' => false
            )
        );
        craft()->amForms_install->installSettings($settings, AmFormsModel::SettingExport);

        // Install a general setting
        $settings = array(
            array(
                'name' => 'Bcc email address',
                'value' => ''
            )
        );
        craft()->amForms_install->installSettings($settings, AmFormsModel::SettingGeneral);
    }
}
