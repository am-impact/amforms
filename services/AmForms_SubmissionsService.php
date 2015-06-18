<?php
namespace Craft;

/**
 * AmForms - Submissions service
 */
class AmForms_SubmissionsService extends BaseApplicationComponent
{
    private $_activeSubmissions = array();

    /**
     * Returns a criteria model for AmForms_Submission elements.
     *
     * @param array $attributes
     *
     * @throws Exception
     * @return ElementCriteriaModel
     */
    public function getCriteria(array $attributes = array())
    {
        return craft()->elements->getCriteria(AmFormsModel::ElementTypeSubmission, $attributes);
    }

    /**
     * Get all submissions.
     *
     * @return AmForms_SubmissionModel|array|null
     */
    public function getAllSubmissions()
    {
        return $this->getCriteria(array('order' => 'name'))->find();
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
        return $this->getCriteria(array('limit' => 1, 'id' => $id))->first();
    }

    /**
     * Set an active front-end submission.
     *
     * @param AmForms_SubmissionModel $submission
     */
    public function setActiveSubmission(AmForms_SubmissionModel $submission)
    {
        $this->_activeSubmissions[ $submission->form->handle ] = $submission;
    }

    /**
     * Get an active front-end submission based on a form.
     *
     * @param AmForms_FormModel $form
     *
     * @return AmForms_SubmissionModel
     */
    public function getActiveSubmission(AmForms_FormModel $form)
    {
        if (isset($this->_activeSubmissions[$form->handle])) {
            return $this->_activeSubmissions[$form->handle];
        }

        return new AmForms_SubmissionModel();
    }

    /**
     * Save a submission.
     *
     * @param AmForms_SubmissionModel $submission
     *
     * @throws Exception
     * @return bool
     */
    public function saveSubmission(AmForms_SubmissionModel $submission)
    {
        $isNewSubmission = ! $submission->id;

        // If we don't need to save it, return a success for other events
        if ($isNewSubmission && ! $submission->form->submissionEnabled) {
            return true;
        }

        // Get the submission record
        if ($submission->id) {
            $submissionRecord = AmForms_SubmissionRecord::model()->findById($submission->id);

            if (! $submissionRecord) {
                throw new Exception(Craft::t('No submission exists with the ID “{id}”.', array('id' => $submission->id)));
            }
        }
        else {
            $submissionRecord = new AmForms_SubmissionRecord();
        }

        // Submission attributes
        $submissionRecord->setAttributes($submission->getAttributes(), false);

        // Validate the attributes
        $submissionRecord->validate();
        $submission->addErrors($submissionRecord->getErrors());

        if (! $submission->hasErrors()) {

            // Fire an 'onBeforeSaveSubmission' event
            $event = new Event($this, array(
                'submission'      => $submission,
                'isNewSubmission' => $isNewSubmission
            ));
            $this->onBeforeSaveSubmission($event);

            // Is the event giving us the go-ahead?
            if ($event->performAction) {
                $transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

                try {
                    // Submission title based on form's title format
                    $submission->getContent()->title = craft()->templates->renderObjectTemplate($submission->form->titleFormat, $submission);

                    // Save the element!
                    if (craft()->elements->saveElement($submission)) {
                        // Now that we have an element ID, save it on the other stuff
                        if ($isNewSubmission) {
                            $submissionRecord->id = $submission->id;
                        }

                        // Save the submission!
                        $submissionRecord->save(false); // Skip validation now

                        if ($transaction !== null) {
                            $transaction->commit();
                        }

                        // Fire an 'onSaveSubmission' event
                        $this->onSaveSubmission(new Event($this, array(
                            'submission'      => $submission,
                            'isNewSubmission' => $isNewSubmission
                        )));

                        return true;
                    }
                } catch (\Exception $e) {
                    if ($transaction !== null) {
                        $transaction->rollback();
                    }

                    throw $e;
                }
            }
        }

        return false;
    }

    /**
     * Delete a submission.
     *
     * @param AmForms_SubmissionModel $submission
     *
     * @throws Exception
     * @return bool
     */
    public function deleteSubmission(AmForms_SubmissionModel $submission)
    {
        $transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

        try {
            // Delete the element and submission
            craft()->elements->deleteElementById($submission->id);

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
     * Email a submission.
     *
     * @param AmForms_SubmissionModel $submission
     *
     * @return bool
     */
    public function emailSubmission(AmForms_SubmissionModel $submission)
    {
        // Do we even have a form ID?
        if (! $submission->formId) {
            return false;
        }

        // Get form if not already set
        $submission->getForm();
        $form = $submission->form;
        $submission->formName = $form->name;
        if (! $form->notificationEnabled) {
            return false;
        }

        // Get our recipients
        $recipients = ArrayHelper::stringToArray($form->notificationRecipients);
        $recipients = array_unique($recipients);
        if (! count($recipients)) {
            return false;
        }

        // Fire an 'onBeforeEmailSubmission' event
        $event = new Event($this, array(
            'submission' => $submission
        ));
        $this->onBeforeEmailSubmission($event);

        // Is the event giving us the go-ahead?
        if ($event->performAction) {

            // Get email body
            $body = $this->_getEmailBody($submission, $form);

            // Other email attributes
            $subject = Craft::t($form->notificationSubject);
            if ($form->notificationSubject) {
                $subject = craft()->templates->renderObjectTemplate($form->notificationSubject, $submission);
            }

            if ($form->notificationReplyToEmail) {
                $replyTo = craft()->templates->renderObjectTemplate($form->notificationReplyToEmail, $submission);
                if (! filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                    $replyTo = null;
                }
            }

            // Start mailing!
            $success = false;

            // @TODO Mandrill
            $email = new EmailModel();
            $email->htmlBody = $body;
            $email->fromEmail = $form->notificationSenderEmail;
            $email->fromName = $form->notificationSenderName;
            $email->subject = $subject;
            if ($replyTo) {
                $email->replyTo = $replyTo;
            }

            foreach ($recipients as $recipient) {
                $email->toEmail = craft()->templates->renderObjectTemplate($recipient, $submission);

                if (filter_var($email->toEmail, FILTER_VALIDATE_EMAIL)) {
                    // Add variable for email event
                    if (craft()->email->sendEmail($email, array('amFormsSubmission' => $submission))) {
                        $success = true;
                    }
                }
            }

            // Fire an 'onEmailSubmission' event
            $this->onEmailSubmission(new Event($this, array(
                'success'    => $success,
                'submission' => $submission
            )));

            return $success;
        }

        return false;
    }

    /**
     * Fires an 'onBeforeSaveSubmission' event.
     *
     * @param Event $event
     */
    public function onBeforeSaveSubmission(Event $event)
    {
        $this->raiseEvent('onBeforeSaveSubmission', $event);
    }

    /**
     * Fires an 'onSaveSubmission' event.
     *
     * @param Event $event
     */
    public function onSaveSubmission(Event $event)
    {
        $this->raiseEvent('onSaveSubmission', $event);
    }

    /**
     * Fires an 'onBeforeEmailSubmission' event.
     *
     * @param Event $event
     */
    public function onBeforeEmailSubmission(Event $event)
    {
        $this->raiseEvent('onBeforeEmailSubmission', $event);
    }

    /**
     * Fires an 'onEmailSubmission' event.
     *
     * @param Event $event
     */
    public function onEmailSubmission(Event $event)
    {
        $this->raiseEvent('onEmailSubmission', $event);
    }

    /**
     * Get email body for a submission.
     *
     * @param AmForms_SubmissionModel $submission
     * @param AmForms_FormModel       $form
     *
     * @return string
     */
    private function _getEmailBody(AmForms_SubmissionModel $submission, AmForms_FormModel $form)
    {
        // Email template
        $template = 'email';
        $templatePath = craft()->path->getPluginsPath() . 'amforms/templates/_display/templates/';
        if ($form->notificationTemplate) {
            $pathParts = explode(DIRECTORY_SEPARATOR, $form->notificationTemplate);
            $templateFile = craft()->path->getSiteTemplatesPath() . $form->notificationTemplate;
            if (count($pathParts) < 2) {
                $templateFile .= DIRECTORY_SEPARATOR . $template;
            }

            foreach (craft()->config->get('defaultTemplateExtensions') as $extension) {
                if (IOHelper::fileExists($templateFile . '.' . $extension)) {
                    if (count($pathParts) > 1) {
                        // We set a specific template
                        $template = $pathParts[ (count($pathParts) - 1) ];
                        $templatePath = craft()->path->getSiteTemplatesPath() . str_replace(DIRECTORY_SEPARATOR . $template, '', implode(DIRECTORY_SEPARATOR, $pathParts));
                    }
                    else {
                        // Only a folder was given, so still the email template
                        $templatePath = craft()->path->getSiteTemplatesPath() . $form->notificationTemplate;
                    }
                }
            }
        }

        // Set Craft template path
        craft()->path->setTemplatesPath($templatePath);

        // Get email body
        $body = craft()->templates->render($template, array(
            'tabs' => $form->getFieldLayout()->getTabs(),
            'form' => $form,
            'submission' => $submission
        ));

        // Reset templates path
        craft()->path->setTemplatesPath(craft()->path->getCpTemplatesPath());

        return $body;
    }
}