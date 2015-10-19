<?php

namespace Craft;

/**
 * Forms for Craft.
 *
 * @author    Hubert Prein
 */
class AmForms_SchematicService extends BaseApplicationComponent
{
    /**
     * Export forms.
     *
     * @return array
     */
    public function export()
    {
        $forms = craft()->amForms_forms->getAllForms();

        $formDefinitions = array();

        foreach ($forms as $form) {
            $formDefinitions[$form->handle] = $this->getFormDefinition($form);
        }

        return $formDefinitions;
    }

    /**
     * Get form definition.
     *
     * @param AmForms_FormModel $form
     *
     * @return array
     */
    private function getFormDefinition(AmForms_FormModel $form)
    {
        return array(
            'name'                      => $form->name,
            'fieldLayout'               => craft()->schematic_fields->getFieldLayoutDefinition($form->getFieldLayout()),
            'redirectEntryId'           => $form->redirectEntryId,
            'handle'                    => $form->handle,
            'titleFormat'               => $form->titleFormat,
            'submitAction'              => $form->submitAction,
            'submitButton'              => $form->submitButton,
            'afterSubmitText'           => $form->afterSubmitText,
            'submissionEnabled'         => $form->submissionEnabled,
            'sendCopy'                  => $form->sendCopy,
            'sendCopyTo'                => $form->sendCopyTo,
            'notificationEnabled'       => $form->notificationEnabled,
            'notificationFilesEnabled'  => $form->notificationFilesEnabled,
            'notificationRecipients'    => $form->notificationRecipients,
            'notificationSubject'       => $form->notificationSubject,
            'notificationSenderName'    => $form->notificationSenderName,
            'notificationReplyToEmail'  => $form->notificationReplyToEmail,
            'formTemplate'              => $form->formTemplate,
            'tabTemplate'               => $form->tabTemplate,
            'fieldTemplate'             => $form->fieldTemplate,
            'notificationTemplate'      => $form->notificationTemplate,
        );
    }

    /**
     * Attempt to import forms.
     *
     * @param array $formDefinitions
     * @param bool  $force If set to true forms not included in the import will be deleted
     *
     * @return Schematic_ResultModel
     */
    public function import($formDefinitions, $force = false)
    {
        $result = new Schematic_ResultModel();

        if (empty($formDefinitions)) {
            // Ignore importing globals.
            return $result;
        }

        $forms = craft()->amForms_forms->getAllForms('handle');

        foreach ($formDefinitions as $formHandle => $formDefinition) {
            $form = array_key_exists($formHandle, $forms)
                ? $forms[$formHandle]
                : new AmForms_FormModel();
            $this->populateFormModel($form, $formDefinition, $formHandle);

            // Save form via craft
            if (!craft()->amForms_forms->saveForm($form)) {
                return $result->error($form->getAllErrors());
            }
            unset($forms[$formHandle]);
        }

        if ($force) {
            foreach ($forms as $form) {
                craft()->amForms_form->deleteForm($form);
            }
        }

        return $result;
    }

    /**
     * Populate form.
     *
     * @param AmForms_FormModel $form
     * @param array             $formDefinition
     * @param string            $formHandle
     */
    private function populateFormModel(AmForms_FormModel $form, array $formDefinition, $formHandle)
    {
        $form->setAttributes(array(
            'handle'                    => $formHandle,
            'name'                      => $formDefinition['name'],
            'titleFormat'               => $form->titleFormat,
            'submitAction'              => $form->submitAction,
            'submitButton'              => $form->submitButton,
            'afterSubmitText'           => $form->afterSubmitText,
            'submissionEnabled'         => $form->submissionEnabled,
            'sendCopy'                  => $form->sendCopy,
            'sendCopyTo'                => $form->sendCopyTo,
            'notificationEnabled'       => $form->notificationEnabled,
            'notificationFilesEnabled'  => $form->notificationFilesEnabled,
            'notificationRecipients'    => $form->notificationRecipients,
            'notificationSubject'       => $form->notificationSubject,
            'notificationSenderName'    => $form->notificationSenderName,
            'notificationReplyToEmail'  => $form->notificationReplyToEmail,
            'formTemplate'              => $form->formTemplate,
            'tabTemplate'               => $form->tabTemplate,
            'fieldTemplate'             => $form->fieldTemplate,
            'notificationTemplate'      => $form->notificationTemplate,
        ));
        
        craft()->content->fieldContext = 'amforms';
        craft()->content->contentTable = 'amforms_content';

        $fieldLayout = craft()->schematic_fields->getFieldLayout($formDefinition['fieldLayout']);

        craft()->content->fieldContext = 'global';
        craft()->content->contentTable = 'content';

        $form->setFieldLayout($fieldLayout);
    }
}
