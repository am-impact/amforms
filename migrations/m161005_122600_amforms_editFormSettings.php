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
        $forms = craft()->amForms_forms->getAllForms();
        if ($forms) {
            foreach ($forms as $form) {
                // Set afterSubmit
                if (! empty($form->afterSubmitText)) {
                    $form->afterSubmit = 'afterSubmitText';
                }
                elseif (! empty($form->redirectEntryId)) {
                    $form->afterSubmit = 'redirectEntryId';
                }
                elseif (! empty($form->redirectUrl)) {
                    $form->afterSubmit = 'redirectUrl';
                }
                elseif (! empty($form->submitAction)) {
                    $form->afterSubmit = 'submitAction';
                }
                else {
                    $form->afterSubmit = 'afterSubmitText';
                }

                // Save form!
                craft()->amForms_forms->saveForm($form);
            }
        }
    }
}
