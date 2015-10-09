<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m151009_122433_amforms_addGlobalTemplateSettings extends BaseMigration
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
				'name' => 'Form template',
				'value' => ''
			),
			array(
				'name' => 'Tab template',
				'value' => ''
			),
			array(
				'name' => 'Field template',
				'value' => ''
			),
			array(
				'name' => 'Notification template',
				'value' => ''
			),
		);
		return craft()->amForms_install->installSettings($settings, AmFormsModel::SettingsTemplatePaths);
	}
}
