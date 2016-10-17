<?php

namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m161017_100256_amforms_addConfirmationTemplateSettings extends BaseMigration
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
				'name' => 'Confirmation template',
				'value' => ''
			),
		);
		return craft()->amForms_install->installSettings($settings, AmFormsModel::SettingsTemplatePaths);
	}
}
