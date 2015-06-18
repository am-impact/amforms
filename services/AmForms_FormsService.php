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
        // Update redirectUri?
        if ($form->redirectUri) {
            $vars = array(
                'siteUrl' => craft()->getSiteUrl()
            );
            $form->redirectUri = craft()->templates->renderObjectTemplate($form->redirectUri, $vars);
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