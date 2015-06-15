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
}