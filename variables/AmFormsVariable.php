<?php
namespace Craft;

class AmFormsVariable
{
    /**
     * Get the Plugin's name.
     *
     * @example {{ craft.amForms.name }}
     * @return string
     */
    public function getName()
    {
        $plugin = craft()->plugins->getPlugin('amforms');
        return $plugin->getName();
    }

    /**
     * Get a setting value by their handle and type.
     *
     * @param string $handle
     * @param string $type
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    public function getSettingValue($handle, $type, $defaultValue = null)
    {
        return craft()->amForms_settings->getSettingValue($handle, $type, $defaultValue);
    }

    /**
     * Get a setting value by their handle and type.
     *
     * @param string $handle
     * @param string $type
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    public function getSettingsValueByHandleAndType($handle, $type, $defaultValue = null)
    {
        return $this->getSettingValue($handle, $type, $defaultValue);
    }


    // Field methods
    // =========================================================================

    /**
     * Get proper field types.
     *
     * @param array $fieldTypes All Craft's fieldtypes.
     *
     * @return array
     */
    public function getProperFieldTypes($fieldTypes)
    {
        return craft()->amForms_fields->getProperFieldTypes($fieldTypes);
    }

    /**
     * Get field handles.
     *
     * @return array
     */
    public function getFieldHandles()
    {
        $handles = array();
        $fields = craft()->fields->getAllFields('handle', AmFormsModel::FieldContext);
        foreach ($fields as $field) {
            $handles[] = array('label' => $field->name, 'value' => $field->handle);
        }

        return $handles;
    }


    // Submission methods
    // =========================================================================

    /**
     * Returns a criteria model for AmForms_Submission elements.
     *
     * @param array $attributes
     *
     * @return ElementCriteriaModel
     */
    public function submissions($attributes = array())
    {
        return craft()->amForms_submissions->getCriteria($attributes);
    }

    /**
     * Get a submission by its ID.
     *
     * @param int  $id
     * @param bool $setAsActive [Optional] Set as active submission, for editing purposes.
     *
     * @return AmForms_SubmissionModel|null
     */
    public function getSubmissionById($id, $setAsActive = false)
    {
        if ($setAsActive) {
            // Get the submission
            $submission = $this->getSubmissionById($id);
            if (! $submission) {
                craft()->amForms->handleError(Craft::t('No submission exists with the ID “{id}”.', array('id' => $id)));
                return false;
            }

            // Get the form
            $form = $submission->getForm();
            if (! $form) {
                craft()->amForms->handleError(Craft::t('No form exists with the ID “{id}”.', array('id' => $submission->formId)));
                return false;
            }

            // Set active submission
            craft()->amForms_submissions->setActiveSubmission($submission);

            return $submission;
        }
        return craft()->amForms_submissions->getSubmissionById($id);
    }

    /**
     * Display a submission to edit and save it.
     *
     * @param int $id
     *
     * @return string
     */
    public function displaySubmission($id)
    {
        // Get the submission
        $submission = $this->getSubmissionById($id);
        if (! $submission) {
            craft()->amForms->handleError(Craft::t('No submission exists with the ID “{id}”.', array('id' => $id)));
            return false;
        }

        // Get the form
        $form = $submission->getForm();
        if (! $form) {
            craft()->amForms->handleError(Craft::t('No form exists with the ID “{id}”.', array('id' => $submission->formId)));
            return false;
        }

        // Set active submission
        craft()->amForms_submissions->setActiveSubmission($submission);

        // Display the edit form!
        return craft()->amForms_forms->displayForm($form);
    }


    // Form methods
    // =========================================================================

    /**
     * Get a form by its ID.
     *
     * @param int $id
     *
     * @return AmForms_FormModel|null
     */
    public function getFormById($id)
    {
        return craft()->amForms_forms->getFormById($id);
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
        return craft()->amForms_forms->getFormByHandle($handle);
    }

    /**
     * Get all forms.
     *
     * @return array
     */
    public function getAllForms()
    {
        return craft()->amForms_forms->getAllForms();
    }

    /**
     * Get a namespace for a form.
     *
     * @param AmForms_FormModel $form
     *
     * @return string
     */
    public function getNamespaceForForm(AmForms_FormModel $form)
    {
        return craft()->amForms_forms->getNamespaceForForm($form);
    }

    /**
     * Get a form by its handle.
     *
     * @param string $handle
     *
     * @return AmForms_FormModel|null
     */
    public function getForm($handle)
    {
        // Get the form
        $form = $this->getFormByHandle($handle);
        if (! $form) {
            craft()->amForms->handleError(Craft::t('No form exists with the handle “{handle}”.', array('handle' => $handle)));
            return false;
        }
        return $form;
    }

    /**
     * Display a form.
     *
     * @param string $handle
     *
     * @return string
     */
    public function displayForm($handle)
    {
        // Get the form
        $form = $this->getFormByHandle($handle);
        if (! $form) {
            craft()->amForms->handleError(Craft::t('No form exists with the handle “{handle}”.', array('handle' => $handle)));
            return false;
        }
        return craft()->amForms_forms->displayForm($form);
    }


    // Anti spam methods
    // =========================================================================

    /**
     * Display AntiSpam widget.
     *
     * @return bool|string
     */
    public function displayAntispam()
    {
        return craft()->amForms_antispam->render();
    }

    /**
     * Display a reCAPTCHA widget.
     *
     * @return bool|string
     */
    public function displayRecaptcha()
    {
        return craft()->amForms_recaptcha->render();
    }
}
