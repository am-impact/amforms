<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m171006_111516_amforms_addSubmissionAuthor extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$this->addColumnAfter('amforms_submissions', 'authorId', ColumnType::Int, 'id');

		return true;
	}
}
