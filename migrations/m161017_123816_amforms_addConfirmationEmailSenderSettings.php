<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m161017_123816_amforms_addConfirmationEmailSenderSettings extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$this->addColumnAfter('amforms_forms', 'confirmationSenderName', AttributeType::String, 'notificationSenderName');
		$this->addColumnAfter('amforms_forms', 'confirmationSenderEmail', AttributeType::String, 'notificationSenderEmail');

		// Craft email settings
		$settings = craft()->email->getSettings();
		$systemEmail = !empty($settings['emailAddress']) ? $settings['emailAddress'] : '';
		$systemName =  !empty($settings['senderName']) ? $settings['senderName'] : '';

		$this->update('amforms_forms', array(
			'confirmationSenderName' => $systemName,
			'confirmationSenderEmail' => $systemEmail
		), array('and', 'confirmationSenderName IS NULL', 'confirmationSenderEmail IS NULL'));

		return true;
	}
}
