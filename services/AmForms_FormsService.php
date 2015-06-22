<?php
namespace Craft;

/**
 * AmForms - Forms service
 */
class AmForms_FormsService extends BaseApplicationComponent
{
    /**
     * Returns a criteria model for AmForms_Form elements.
     *
     * @param array $attributes
     *
     * @throws Exception
     * @return ElementCriteriaModel
     */
    public function getCriteria(array $attributes = array())
    {
        return craft()->elements->getCriteria(AmFormsModel::ElementTypeForm, $attributes);
    }

    /**
     * Get all forms.
     *
     * @return AmForms_FormModel|array|null
     */
    public function getAllForms()
    {
        return $this->getCriteria(array('order' => 'name'))->find();
    }

    /**
     * Get a form by its ID.
     *
     * @param int $id
     *
     * @return AmForms_FormModel|null
     */
    public function getFormById($id)
    {
        return $this->getCriteria(array('limit' => 1, 'id' => $id))->first();
    }

    /**
     * Get a form by its handle.
     *
     * @param string $handle
     *
     * @return AmForms_FormModel|null
     */
    public function getFormByHandle($handle)
    {
        return $this->getCriteria(array('limit' => 1, 'handle' => $handle))->first();
    }

    /**
     * Save a form.
     *
     * @param AmForms_FormModel $form
     *
     * @throws Exception
     * @return bool
     */
    public function saveForm(AmForms_FormModel $form)
    {
        $isNewForm = ! $form->id;

        // Get the Form record
        if ($form->id) {
            $formRecord = AmForms_FormRecord::model()->findById($form->id);

            if (! $formRecord) {
                throw new Exception(Craft::t('No form exists with the ID “{id}”.', array('id' => $form->id)));
            }

            $oldForm = AmForms_FormModel::populateModel($formRecord);
        }
        else {
            $formRecord = new AmForms_FormRecord();
        }

        // Form attributes
        $formRecord->setAttributes($form->getAttributes(), false);

        // Validate the attributes
        $formRecord->validate();
        $form->addErrors($formRecord->getErrors());

        // Is submissions or notifications enabled?
        if (! $form->submissionEnabled && ! $form->notificationEnabled) {
            $form->addError('submissionEnabled', Craft::t('Submissions or notifications must be enabled, otherwise you will lose the submission.'));
            $form->addError('notificationEnabled', Craft::t('Notifications or submissions must be enabled, otherwise you will lose the submission.'));
        }

        if (! $form->hasErrors()) {
            $transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

            try {
                // Do we need to delete an old field layout?
                if (! $isNewForm) {
                    $oldLayout = $oldForm->getFieldLayout();

                    if ($oldLayout) {
                        craft()->fields->deleteLayoutById($oldLayout->id);
                    }
                }

                // Do we have a new field layout?
                if (count($form->getFieldLayout()->getFields()) > 0) {
                    $fieldLayout = $form->getFieldLayout();

                    // Save the field layout
                    craft()->fields->saveLayout($fieldLayout);

                    // Assign layout to our form
                    $formRecord->fieldLayoutId = $fieldLayout->id;
                }
                else {
                    // No field layout given
                    $formRecord->fieldLayoutId = null;
                }

                // Save the element!
                if (craft()->elements->saveElement($form)) {
                    // Now that we have an element ID, save it on the other stuff
                    if ($isNewForm) {
                        $formRecord->id = $form->id;
                    }

                    // Save the form!
                    $formRecord->save(false); // Skip validation now

                    if ($transaction !== null) {
                        $transaction->commit();
                    }

                    return true;
                }
            } catch (\Exception $e) {
                if ($transaction !== null) {
                    $transaction->rollback();
                }

                throw $e;
            }
        }

        return false;
    }

    /**
     * Delete a form.
     *
     * @param AmForms_FormModel $form
     *
     * @throws Exception
     * @return bool
     */
    public function deleteForm(AmForms_FormModel $form)
    {
        $transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

        try {
            // Delete the field layout
            craft()->fields->deleteLayoutById($form->fieldLayoutId);

            // Delete submission elements
            $submissionIds = craft()->db->createCommand()
                ->select('id')
                ->from('amforms_submissions')
                ->where(array('formId' => $form->id))
                ->queryColumn();
            craft()->elements->deleteElementById($submissionIds);

            // Delete the element and form
            craft()->elements->deleteElementById($form->id);

            if ($transaction !== null) {
                $transaction->commit();
            }

            return true;
        } catch (\Exception $e) {
            if ($transaction !== null) {
                $transaction->rollback();
            }

            throw $e;
        }

        return false;
    }

    /**
     * Display a form.
     *
     * @param AmForms_FormModel $form
     *
     * @return string
     */
    public function displayForm(AmForms_FormModel $form)
    {
        // Get submission model
        $submission = craft()->amForms_submissions->getActiveSubmission($form);

        // Update redirectUri?
        if ($form->redirectUri) {
            $vars = array(
                'siteUrl' => craft()->getSiteUrl()
            );
            $form->redirectUri = craft()->templates->renderObjectTemplate($form->redirectUri, $vars);
        }

        // Plugin's default template path
        $templatePath = craft()->path->getPluginsPath() . 'amforms/templates/_display/templates/';

        // Build field HTML
        $tabs = array();
        $supportedFields = craft()->amForms_fields->getSupportedFieldTypes();
        $fieldTemplateInfo = craft()->amForms->getDisplayTemplateInfo('field', $form->fieldTemplate);
        foreach ($form->getFieldLayout()->getTabs() as $tab) {
            // Tab information
            $tabs[$tab->id] = array(
                'info'   => $tab,
                'fields' => array()
            );

            // Tab fields
            $fields = $tab->getFields();
            foreach ($fields as $layoutField) {
                // Get actual field
                $field = $layoutField->getField();
                if (! in_array($field->type, $supportedFields)) {
                    // We don't display unsupported fields
                    continue;
                }

                // Reset templates path for input and get field input
                craft()->path->setTemplatesPath($templatePath);
                $fieldInfo = craft()->fields->populateFieldType($field, $submission);
                $input = $fieldInfo->getInputHtml($field->handle, $submission->getFieldValue($field->handle));

                // Get field HTML
                craft()->path->setTemplatesPath($fieldTemplateInfo['path']);
                $tabs[$tab->id]['fields'][] = craft()->templates->render($fieldTemplateInfo['template'], array(
                    'form'     => $form,
                    'field'    => $field,
                    'input'    => $input,
                    'required' => $field->required,
                    'element'  => $submission
                ));
            }
        }

        // Build tab HTML
        $variables = array(
            'form'    => $form,
            'tabs'    => $tabs,
            'element' => $submission
        );
        $bodyHtml = craft()->amForms->renderDisplayTemplate('tab', $form->tabTemplate, $variables);

        // Use reCAPTCHA?
        $recaptchaHtml = craft()->amForms_recaptcha->render();

        // Build our complete form
        $variables = array(
            'form'      => $form,
            'body'      => $bodyHtml,
            'recaptcha' => $recaptchaHtml,
            'element'   => $submission
        );

        $formHtml = craft()->amForms->renderDisplayTemplate('form', $form->formTemplate, $variables);

        // Parse form
        return new \Twig_Markup($formHtml, craft()->templates->getTwig()->getCharset());
    }
}