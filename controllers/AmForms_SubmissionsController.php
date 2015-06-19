<?php
namespace Craft;

/**
 * AmForms - Submissions controller
 */
class AmForms_SubmissionsController extends BaseController
{
    protected $allowAnonymous = array('actionSaveSubmission');

    /**
     * Show submissions.
     */
    public function actionIndex()
    {
        $variables = array(
            'elementType' => AmFormsModel::ElementTypeSubmission
        );
        $this->renderTemplate('amForms/submissions/index', $variables);
    }

    /**
     * Edit a submission.
     *
     * @param array $variables
     */
    public function actionEditSubmission(array $variables = array())
    {
        // Do we have a submission model?
        if (! isset($variables['submission'])) {
            // We require a submission ID
            if (empty($variables['submissionId'])) {
                throw new HttpException(404);
            }

            // Get submission if available
            $submission = craft()->amForms_submissions->getSubmissionById($variables['submissionId']);
            if (! $submission) {
                throw new Exception(Craft::t('No submission exists with the ID “{id}”.', array('id' => $variables['submissionId'])));
            }

            // Get form if available
            $form = craft()->amForms_forms->getFormById($submission->formId);
            if (! $form) {
                throw new Exception(Craft::t('No form exists with the ID “{id}”.', array('id' => $submission->formId)));
            }

            // Get tabs
            $tabs = array();
            $layoutTabs = $submission->getFieldLayout()->getTabs();
            foreach ($layoutTabs as $tab) {
                $tabs[$tab->id] = array(
                    'label' => $tab->name,
                    'url' => '#tab' . $tab->sortOrder
                );
            }

            // Set variables
            $variables['submission'] = $submission;
            $variables['form'] = $form;
            $variables['tabs'] = $tabs;
            $variables['layoutTabs'] = $layoutTabs;
        }
        $this->renderTemplate('amforms/submissions/_edit', $variables);
    }

    /**
     * Save a form submission.
     */
    public function actionSaveSubmission()
    {
        $this->requirePostRequest();

        // Get the form
        $handle = craft()->request->getRequiredPost('handle');
        $form = craft()->amForms_forms->getFormByHandle($handle);
        if (! $form) {
            throw new Exception(Craft::t('No form exists with the handle “{handle}”.', array('handle' => $handle)));
        }

        // Get the submission from CP?
        if (craft()->request->isCpRequest()) {
            $submissionId = craft()->request->getPost('submissionId');
        }

        // Get the submission
        if (isset($submissionId)) {
            $submission = craft()->amForms_submissions->getSubmissionById($submissionId);

            if (! $submission) {
                throw new Exception(Craft::t('No submission exists with the ID “{id}”.', array('id' => $submissionId)));
            }
        }
        else {
            $submission = new AmForms_SubmissionModel();
        }

        // Add the form to the submission
        $submission->form = $form;
        $submission->formId = $form->id;

        // Set attributes
        $fieldsLocation = craft()->request->getParam('fieldsLocation', 'fields');
        $submission->ipAddress = craft()->request->getUserHostAddress();
        $submission->userAgent = craft()->request->getUserAgent();
        $submission->setContentFromPost($fieldsLocation);
        $submission->setContentPostLocation($fieldsLocation);

        // Front-end submission, trigger reCAPTCHA?
        if (! craft()->request->isCpRequest() && craft()->amForms_settings->isSettingValueEnabled('useRecaptcha', AmFormsModel::SettingRecaptcha)) {
            $captcha = craft()->request->getPost('g-recaptcha-response');
            $submission->spamFree = craft()->amForms_recaptcha->verify($captcha);
        }

        // Save submission
        if (craft()->amForms_submissions->saveSubmission($submission)) {
            // Notification
            if (! craft()->request->isCpRequest()) {
                craft()->amForms_submissions->emailSubmission($submission);
            }

            // Redirect
            if (craft()->request->isAjaxRequest()) {
                $this->returnJson(array('success' => true));
            }
            elseif (craft()->request->isCpRequest()) {
                craft()->userSession->setNotice(Craft::t('Submission saved.'));

                $this->redirectToPostedUrl($submission);
            }
            else {
                $this->_doRedirect($submission);
            }
        }
        else {
            if (craft()->request->isAjaxRequest()) {
                $return = array(
                    'success' => false,
                    'errors' => $submission->getErrors()
                );
                $this->returnJson($return);
            }
            elseif (craft()->request->isCpRequest()) {
                craft()->userSession->setError(Craft::t('Couldn’t save submission.'));

                // Send the submission back to the template
                craft()->urlManager->setRouteVariables(array(
                    'submission' => $submission
                ));
            }
            else {
                craft()->amForms_submissions->setActiveSubmission($submission);
            }
        }
    }

    /**
     * Delete a submission.
     *
     * @return void
     */
    public function actionDeleteSubmission()
    {
        $this->requirePostRequest();

        // Get the submission
        $submissionId = craft()->request->getRequiredPost('submissionId');
        $submission = craft()->amForms_submissions->getSubmissionById($submissionId);
        if (! $submission) {
            throw new Exception(Craft::t('No submission exists with the ID “{id}”.', array('id' => $submissionId)));
        }

        // Delete submission
        $success = craft()->amForms_submissions->deleteSubmission($submission);

        $this->redirectToPostedUrl($submission);
    }

    /**
     * Do redirect with {placeholders} support.
     *
     * @param AmForms_SubmissionModel $submission
     */
    private function _doRedirect(AmForms_SubmissionModel $submission)
    {
        $vars = array_merge(
            array(
                'siteUrl' => craft()->getSiteUrl()
            ),
            $submission->getContent()->getAttributes(),
            $submission->getAttributes()
        );

        $this->redirectToPostedUrl($vars);
    }
}