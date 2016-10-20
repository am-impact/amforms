<?php

namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName.
 */
class m161005_122600_amforms_editFormSettings extends BaseMigration
{
    /**
     * Any migration code in here is wrapped inside of a transaction.
     *
     * @return bool
     */
    public function safeUp()
    {
        $this->addColumnAfter('amforms_forms', 'afterSubmit', AttributeType::String, 'submitButton');

        // Update existing forms
        $forms = craft()->db->createCommand()
            ->select('*')
            ->from('amforms_forms')
            ->queryAll();
        if ($forms) {
            foreach ($forms as $form) {
                // Set afterSubmit
                $afterSubmit = 'afterSubmitText';

                if (! empty($form['afterSubmitText'])) {
                    $afterSubmit = 'afterSubmitText';
                }
                elseif (! empty($form['redirectEntryId'])) {
                    $afterSubmit = 'redirectEntryId';
                }
                elseif (! empty($form['redirectUrl'])) {
                    $afterSubmit = 'redirectUrl';
                }
                elseif (! empty($form['submitAction'])) {
                    $afterSubmit = 'submitAction';
                }

                // Save form!
                craft()->db->createCommand()->update('amforms_forms', array('afterSubmit' => $afterSubmit), 'id = :id', array(':id' => $form['id']));
            }
        }
    }
}
