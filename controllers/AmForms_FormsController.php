<?php
namespace Craft;

/**
 * AmForms - Forms controller
 */
class AmForms_FormsController extends BaseController
{
    /**
     * Make sure the current has access.
     */
    public function __construct()
    {
        $user = craft()->userSession->getUser();
        if (! $user->can('accessAmFormsForms')) {
            throw new HttpException(403, Craft::t('This action may only be performed by users with the proper permissions.'));
        }
    }

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

        // Fields per set setting
        $fieldsPerSet = craft()->amForms_settings->getSettingByHandleAndType('fieldsPerSet', AmFormsModel::SettingGeneral);
        $fieldsPerSet = ($fieldsPerSet && is_numeric($fieldsPerSet->value)) ? (int) $fieldsPerSet->value : 8;

        // Get available fields with our context
        $groupId = 1;
        $counter = 1;
        $variables['groups'] = array();
        $fields = craft()->fields->getAllFields('id', AmFormsModel::FieldContext);
        foreach ($fields as $field) {
            if ($counter % $fieldsPerSet == 1) {
                $groupId ++;
                $counter = 1;
            }
            $variables['groups'][$groupId]['fields'][] = $field;
            $counter ++;
        }

        // Get redirectEntryId elementType
        $variables['entryElementType'] = craft()->elements->getElementType(ElementType::Entry);

        // Get available attributes
        $variables['availableAttributes'] = array();
        $submission = new AmForms_SubmissionModel();
        $ignoreAttributes = array(
            'slug', 'uri', 'root', 'lft', 'rgt', 'level', 'searchScore', 'localeEnabled', 'archived', 'spamFree'
        );
        foreach ($submission->getAttributes() as $attribute => $value) {
            if (! in_array($attribute, $ignoreAttributes)) {
                $variables['availableAttributes'][] = $attribute;
            }
        }
        foreach ($fields as $field) {
            $variables['availableAttributes'][] = $field['handle'];
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
        if ($formId && $formId !== 'copy') {
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

        // Get redirectEntryId
        $redirectEntryId = craft()->request->getPost('redirectEntryId');

        // Form attributes
        $form->redirectEntryId          = $redirectEntryId && is_array($redirectEntryId) && count($redirectEntryId) ? $redirectEntryId[0] : null;
        $form->name                     = craft()->request->getPost('name');
        $form->handle                   = craft()->request->getPost('handle');
        $form->titleFormat              = craft()->request->getPost('titleFormat');
        $form->submitAction             = craft()->request->getPost('submitAction');
        $form->submitButton             = craft()->request->getPost('submitButton');
        $form->afterSubmit              = craft()->request->getPost('afterSubmit');
        $form->afterSubmitText          = craft()->request->getPost('afterSubmitText');
        $form->submissionEnabled        = craft()->request->getPost('submissionEnabled');
        $form->displayTabTitles         = craft()->request->getPost('displayTabTitles');
        $form->redirectUrl              = craft()->request->getPost('redirectUrl');
        $form->sendCopy                 = craft()->request->getPost('sendCopy');
        $form->sendCopyTo               = craft()->request->getPost('sendCopyTo');
        $form->notificationEnabled      = craft()->request->getPost('notificationEnabled');
        $form->notificationFilesEnabled = craft()->request->getPost('notificationFilesEnabled');
        $form->notificationRecipients   = craft()->request->getPost('notificationRecipients');
        $form->notificationSubject      = craft()->request->getPost('notificationSubject');
        $form->confirmationSubject      = craft()->request->getPost('confirmationSubject');
        $form->notificationSenderName   = craft()->request->getPost('notificationSenderName');
        $form->confirmationSenderName   = craft()->request->getPost('confirmationSenderName');
        $form->notificationSenderEmail  = craft()->request->getPost('notificationSenderEmail');
        $form->confirmationSenderEmail  = craft()->request->getPost('confirmationSenderEmail');
        $form->notificationReplyToEmail = craft()->request->getPost('notificationReplyToEmail');
        $form->formTemplate             = craft()->request->getPost('formTemplate', $form->formTemplate);
        $form->tabTemplate              = craft()->request->getPost('tabTemplate', $form->tabTemplate);
        $form->fieldTemplate            = craft()->request->getPost('fieldTemplate', $form->fieldTemplate);
        $form->notificationTemplate     = craft()->request->getPost('notificationTemplate', $form->notificationTemplate);
        $form->confirmationTemplate     = craft()->request->getPost('confirmationTemplate', $form->confirmationTemplate);

        // Duplicate form, so the name and handle are taken
        if ($formId && $formId === 'copy') {
            craft()->amForms_forms->getUniqueNameAndHandle($form);
        }

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
        if (craft()->amForms_forms->deleteForm($form)) {
            craft()->userSession->setNotice(Craft::t('Form deleted.'));
        }
        else {
            craft()->userSession->setError(Craft::t('Couldn’t delete form.'));
        }

        $this->redirectToPostedUrl($form);
    }
}
