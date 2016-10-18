<?php

namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m161017_095717_amforms_addConfirmationTemplate extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$this->addColumnAfter('amforms_forms', 'confirmationTemplate', AttributeType::String, 'notificationTemplate');

		return true;
	}
}
