<?php
namespace Craft;

/**
 * AmForms - Forms controller
 */
class AmForms_FormsController extends BaseController
{
    /**
     * Show forms.
     */
    public function actionIndex()
    {
        $variables = array(
            'elementType' => AmFormsModel::ElementTypeForm
        );
        $this->renderTemplate('amForms/forms/index', $variables);
    }

    /**
     * Create or edit a form.
     *
     * @param array $variables
     */
    public function actionEditForm(array $variables = array())
    {
        // Do we have a form model?
        if (! isset($variables['form'])) {
            // Get form if available
            if (! empty($variables['formId'])) {
                $variables['form'] = craft()->amForms_forms->getFormById($variables['formId']);

                if (! $variables['form']) {
                    throw new Exception(Craft::t('No form exists with the ID “{id}”.', array('id' => $variables['formId'])));
                }
            }
            else {
                $variables['form'] = new AmForms_FormModel();
            }
        }
        $this->renderTemplate('amforms/forms/_edit', $variables);
    }

    /**
     * Save a form.
     */
    public function actionSaveForm()
    {
        $this->requirePostRequest();

        // Get form if available
        $formId = craft()->request->getPost('formId');
        if ($formId) {
            $form = craft()->amForms_forms->getFormById($formId);

            if (! $form) {
                throw new Exception(Craft::t('No form exists with the ID “{id}”.', array('id' => $formId)));
            }
        }
        else {
            $form = new AmForms_FormModel();
        }

        // Field layout
        $fieldLayout = craft()->fields->assembleLayoutFromPost();
        $fieldLayout->type = AmFormsModel::ElementTypeForm;
        $form->setFieldLayout($fieldLayout);

        // Form attributes
        $form->name         = craft()->request->getPost('name');
        $form->handle       = craft()->request->getPost('handle');
        $form->titleFormat  = craft()->request->getPost('titleFormat');
        $form->redirectUri  = craft()->request->getPost('redirectUri');
        $form->submitAction = craft()->request->getPost('submitAction');
        $form->submitButton = craft()->request->getPost('submitButton');

        // Save form
        if (craft()->amForms_forms->saveForm($form)) {
            craft()->userSession->setNotice(Craft::t('Form saved.'));

            $this->redirectToPostedUrl($form);
        }
        else {
            craft()->userSession->setError(Craft::t('Couldn’t save form.'));

            // Send the form back to the template
            craft()->urlManager->setRouteVariables(array(
                'form' => $form
            ));
        }
    }

    /**
     * Delete a form.
     */
    public function actionDeleteForm()
    {
        $this->requirePostRequest();

        // Get form if available
        $formId = craft()->request->getRequiredPost('formId');
        $form = craft()->amForms_forms->getFormById($formId);
        if (! $form) {
            throw new Exception(Craft::t('No form exists with the ID “{id}”.', array('id' => $formId)));
        }

        // Delete form
        craft()->amForms_forms->deleteForm($form);

        $this->redirectToPostedUrl($form);
    }
}