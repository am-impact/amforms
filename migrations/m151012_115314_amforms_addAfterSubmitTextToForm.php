<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m151012_115314_amforms_addAfterSubmitTextToForm extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$this->addColumnAfter('amforms_forms', 'afterSubmitText', ColumnType::Text, 'submitButton');
	}
}
