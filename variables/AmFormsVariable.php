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
     * @param int $id
     *
     * @return AmForms_SubmissionModel|null
     */
    public function getSubmissionById($id)
    {
        return craft()->amForms_submissions->getSubmissionById($id);
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
