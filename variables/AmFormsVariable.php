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
     * Display a form.
     *
     * @param string $handle
     *
     * @return string
     */
    public function displayForm($handle)
    {
        // Get the form
        $form = craft()->amForms_forms->getFormByHandle($handle);
        if (! $form) {
            craft()->amForms->handleError(Craft::t('No form exists with the handle “{handle}”.', array('handle' => $handle)));
            return false;
        }

        // Change the templates path
        craft()->path->setTemplatesPath(craft()->path->getPluginsPath() . 'amforms/templates/_display/templates/');

        // Build our complete form
        $formHtml = craft()->templates->render('form', array(
            'form' => $form,
            'element' => craft()->amForms_submissions->getActiveSubmission($form)
        ));

        // Reset templates path
        craft()->path->setTemplatesPath(craft()->path->getSiteTemplatesPath());

        // Parse form
        return new \Twig_Markup($formHtml, craft()->templates->getTwig()->getCharset());
    }
}