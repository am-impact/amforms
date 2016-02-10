<?php
namespace Craft;

/**
 * AmForms - Forms service
 */
class AmForms_FormsService extends BaseApplicationComponent
{
    private $_fields = array();

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
    public function getAllForms($indexBy = 'id')
    {
        return $this->getCriteria(array('order' => 'name', 'indexBy' => $indexBy))->find();
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
                // Set field context otherwise the layout could fail
                craft()->content->fieldContext = AmFormsModel::FieldContext;
                craft()->content->contentTable = AmFormsModel::FieldContent;

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
            // Delete export files
            craft()->amForms_exports->deleteExportFilesForForm($form);

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
     * Display a field.
     *
     * @param AmForms_FormModel $form
     * @param string            $handle
     *
     * @return string
     */
    public function displayField(AmForms_FormModel $form, $handle)
    {
        // Get submission model
        $submission = craft()->amForms_submissions->getActiveSubmission($form);

        // Get template path
        $fieldTemplateInfo = craft()->amForms->getDisplayTemplateInfo('field', $form->fieldTemplate);
        $templatePath = $fieldTemplateInfo['path'];

        // Get the current templates path so we can restore it at the end of this function
        $siteTemplatesPath = craft()->path->getTemplatesPath();
        $pluginTemplateInfo = craft()->amForms->getDisplayTemplateInfo('field', false);
        $pluginTemplatePath = $pluginTemplateInfo['path'];

        // Do we have the current form fields?
        if (! isset($this->_fields[$form->id])) {
            $this->_fields[$form->id] = array();
            $supportedFields = craft()->amForms_fields->getSupportedFieldTypes();

            // Get tabs
            foreach ($form->getFieldLayout()->getTabs() as $tab) {
                // Get tab's fields
                foreach ($tab->getFields() as $layoutField) {
                    // Get actual field
                    $field = $layoutField->getField();
                    if (! in_array($field->type, $supportedFields)) {
                        // We don't display unsupported fields
                        continue;
                    }

                    // Reset templates path for input and get field input
                    craft()->path->setTemplatesPath($pluginTemplatePath);
                    $fieldInfo = craft()->fields->populateFieldType($field, $submission);

                    craft()->templates->getTwig()->addGlobal('required', $layoutField->required);
                    $input = $fieldInfo->getInputHtml($field->handle, $submission->getFieldValue($field->handle));
                    craft()->templates->getTwig()->addGlobal('required', null);

                    // Get field HTML
                    craft()->path->setTemplatesPath($fieldTemplateInfo['path']);
                    $fieldHtml = craft()->templates->render($fieldTemplateInfo['template'], array(
                        'form'     => $form,
                        'field'    => $field,
                        'input'    => $input,
                        'required' => $layoutField->required,
                        'element'  => $submission
                    ));

                    // Add to fields
                    $this->_fields[$form->id][$field->handle] = $fieldHtml;
                }
            }
        }

        // Restore the templates path variable to it's original value
        craft()->path->setTemplatesPath($siteTemplatesPath);

        // Return field!
        if (isset($this->_fields[$form->id][$handle])) {
            return new \Twig_Markup($this->_fields[$form->id][$handle], craft()->templates->getTwig()->getCharset());
        }
        else {
            return null;
        }
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

        // Build field HTML
        $tabs = array();
        $supportedFields = craft()->amForms_fields->getSupportedFieldTypes();
        $fieldTemplateInfo = craft()->amForms->getDisplayTemplateInfo('field', $form->fieldTemplate);
        $templatePath = $fieldTemplateInfo['path'];

        // Get the current templates path so we can restore it at the end of this function
        $siteTemplatesPath = craft()->path->getTemplatesPath();
        $pluginTemplateInfo = craft()->amForms->getDisplayTemplateInfo('field', false);
        $pluginTemplatePath = $pluginTemplateInfo['path'];

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
                craft()->path->setTemplatesPath($pluginTemplatePath);
                $fieldInfo = craft()->fields->populateFieldType($field, $submission);

                craft()->templates->getTwig()->addGlobal('required', $layoutField->required);
                $input = $fieldInfo->getInputHtml($field->handle, $submission->getFieldValue($field->handle));
                craft()->templates->getTwig()->addGlobal('required', null);

                // Get field HTML
                craft()->path->setTemplatesPath($fieldTemplateInfo['path']);
                $tabs[$tab->id]['fields'][] = craft()->templates->render($fieldTemplateInfo['template'], array(
                    'form'     => $form,
                    'field'    => $field,
                    'input'    => $input,
                    'required' => $layoutField->required,
                    'element'  => $submission
                ));
            }
        }

        // Restore the templates path variable to it's original value
        craft()->path->setTemplatesPath($siteTemplatesPath);

        // Build tab HTML
        $variables = array(
            'form'    => $form,
            'tabs'    => $tabs,
            'element' => $submission
        );
        $bodyHtml = craft()->amForms->renderDisplayTemplate('tab', $form->tabTemplate, $variables);

        // Use AntiSpam?
        $antispamHtml = craft()->amForms_antispam->render();

        // Use reCAPTCHA?
        $recaptchaHtml = craft()->amForms_recaptcha->render();

        // Build our complete form
        $variables = array(
            'form'      => $form,
            'body'      => $bodyHtml,
            'antispam'  => $antispamHtml,
            'recaptcha' => $recaptchaHtml,
            'element'   => $submission
        );

        $formHtml = craft()->amForms->renderDisplayTemplate('form', $form->formTemplate, $variables);

        // Parse form
        return new \Twig_Markup($formHtml, craft()->templates->getTwig()->getCharset());
    }
}
