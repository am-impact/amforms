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

                    // Set field context and content
                    $oldFieldContext = craft()->content->fieldContext;
                    $oldContentTable = craft()->content->contentTable;
                    craft()->content->fieldContext = $submission->getFieldContext();
                    craft()->content->contentTable = $submission->getContentTable();

                    // Save the element!
                    if (craft()->elements->saveElement($submission)) {
                        // Reset field context and content
                        craft()->content->fieldContext = $oldFieldContext;
                        craft()->content->contentTable = $oldContentTable;

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

                    // Reset field context and content
                    craft()->content->fieldContext = $oldFieldContext;
                    craft()->content->contentTable = $oldContentTable;
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
     * @param mixed                   $overrideRecipients [Optional] Override recipients from form settings.
     *
     * @return bool
     */
    public function emailSubmission(AmForms_SubmissionModel $submission, $overrideRecipients = false)
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

        // Send copy?
        if ($form->sendCopy) {
            $sendCopyTo = $submission->{$form->sendCopyTo};
            if (filter_var($sendCopyTo, FILTER_VALIDATE_EMAIL)) {
                $recipients[] = $sendCopyTo;
            }
        }

        if ($overrideRecipients !== false) {
            if (is_array($overrideRecipients) && count($overrideRecipients)) {
                $recipients = $overrideRecipients;
            }
            elseif (is_string($overrideRecipients)) {
                $recipients = ArrayHelper::stringToArray($overrideRecipients);
            }
        }
        $recipients = array_unique($recipients);
        if (! count($recipients)) {
            return false;
        }

        // Overridden recipients skips before email event, so by default, send the email!
        $sendEmail = true;

        // Fire an 'onBeforeEmailSubmission' event
        if ($overrideRecipients === false) {
            $event = new Event($this, array(
                'submission' => $submission
            ));
            $this->onBeforeEmailSubmission($event);

            // Override sendEmail
            $sendEmail = $event->performAction;
        }

        // Is the event giving us the go-ahead?
        if ($sendEmail) {
            // Get email body
            $body = $this->getSubmissionEmailBody($submission);

            // Other email attributes
            if ($form->notificationReplyToEmail) {
                $replyTo = $this->_translatedObjectPlusEnvironment($form->notificationReplyToEmail, $submission);
                if (! filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                    $replyTo = null;
                }
            }

            // Start mailing!
            $success = false;

            // @TODO Mandrill
            $email = new EmailModel();
            $email->htmlBody = $body;
            $email->fromEmail = $this->_translatedObjectPlusEnvironment($form->notificationSenderEmail, $submission);
            $email->fromName = $this->_translatedObjectPlusEnvironment($form->notificationSenderName, $submission);
            $email->subject = $this->_translatedObjectPlusEnvironment($form->notificationSubject, $submission);
            if ($replyTo) {
                $email->replyTo = $replyTo;
            }

            // Add Bcc?
            $bccEmailAddress = craft()->amForms_settings->getSettingsByHandleAndType('bccEmailAddress', AmFormsModel::SettingGeneral);
            if ($bccEmailAddress && $bccEmailAddress->value) {
                $bccAddresses = ArrayHelper::stringToArray($bccEmailAddress->value);
                $bccAddresses = array_unique($bccAddresses);

                if (count($bccAddresses)) {
                    $properBccAddresses = array();

                    foreach ($bccAddresses as $bccAddress) {
                        $bccAddress = $this->_translatedObjectPlusEnvironment($bccAddress, $submission);

                        if (filter_var($bccAddress, FILTER_VALIDATE_EMAIL)) {
                            $properBccAddresses[] = array(
                                'email' => $bccAddress
                            );
                        }
                    }

                    if (count($properBccAddresses)) {
                        $email->bcc = $properBccAddresses;
                    }
                }
            }

            // Add files to the notification?
            if ($form->notificationFilesEnabled) {
                foreach ($submission->getFieldLayout()->getTabs() as $tab) {
                    // Tab fields
                    $fields = $tab->getFields();
                    foreach ($fields as $layoutField) {
                        // Get actual field
                        $field = $layoutField->getField();

                        // Find assets
                        if ($field->type == 'Assets') {
                            foreach ($submission->{$field->handle}->find() as $asset) {
                                if($asset->source->type == 'S3'){
                                    $file = @file_get_contents($asset->url);
                                    // Add asset as attachment
                                    if ($file) {
                                        $email->addStringAttachment($file, $asset->filename);
                                    }
                                } else {
                                    $assetPath = craft()->amForms->getPathForAsset($asset);

                                    // Add asset as attachment
                                    if (IOHelper::fileExists($assetPath . $asset->filename)) {
                                        $email->addAttachment($assetPath . $asset->filename);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Send emails
            foreach ($recipients as $recipient) {
                $email->toEmail = $this->_translatedObjectPlusEnvironment($recipient, $submission);

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
     * Get submission email body.
     *
     * @param AmForms_SubmissionModel $submission
     *
     * @return string
     */
    public function getSubmissionEmailBody(AmForms_SubmissionModel $submission)
    {
        // Get form if not already set
        $submission->getForm();
        $form = $submission->form;

        // Get email body
        $variables = array(
            'tabs' => $form->getFieldLayout()->getTabs(),
            'form' => $form,
            'submission' => $submission
        );
        return craft()->amForms->renderDisplayTemplate('email', $form->notificationTemplate, $variables);
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
     * Parse a string through an object and environment variables.
     *
     * @param string $string
     * @param mixed  $object
     *
     * @return string
     */
    private function _translatedObjectPlusEnvironment($string, $object = null)
    {
        // Parse through object
        if ($object) {
            $string = craft()->templates->renderObjectTemplate($string, $object);
        }

        // Parse through environment variables
        $string = craft()->config->parseEnvironmentString($string);

        // Return translated string
        return Craft::t($string);
    }
}
