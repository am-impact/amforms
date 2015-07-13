<?php
namespace Craft;

/**
 * AmForms - Notes controller
 */
class AmForms_NotesController extends BaseController
{
    /**
     * Display notes
     *
     * @param array $variables
     */
    public function actionDisplayNotes(array $variables = array())
    {
        // Do we have a note model?
        if (! isset($variables['note'])) {
            $variables['note'] = new AmForms_NoteModel();
        }

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

        // Set variables
        $variables['submission'] = $submission;
        $variables['form'] = $form;
        $variables['notes'] = craft()->amForms_notes->getNotesBySubmissionId($variables['submissionId']);

        $this->renderTemplate('amforms/submissions/_notes', $variables);
    }

    /**
     * Save a note.
     */
    public function actionSaveNote()
    {
        $this->requirePostRequest();

        // Get note if available
        $noteId = craft()->request->getPost('noteId');
        if ($noteId) {
            $note = craft()->amForms_notes->getNoteById($noteId);

            if (! $note) {
                throw new Exception(Craft::t('No note exists with the ID “{id}”.', array('id' => $noteId)));
            }
        }
        else {
            $note = new AmForms_NoteModel();
        }

        // Note attributes
        $note->submissionId = craft()->request->getPost('submissionId');
        $note->name = craft()->request->getPost('name');
        $note->text = craft()->request->getPost('text');

        // Save note
        if (craft()->amForms_notes->saveNote($note)) {
            craft()->userSession->setNotice(Craft::t('Note saved.'));

            $this->redirectToPostedUrl($note);
        }
        else {
            craft()->userSession->setError(Craft::t('Couldn’t save note.'));

            // Send the note back to the template
            craft()->urlManager->setRouteVariables(array(
                'note' => $note
            ));
        }
    }

    /**
     * Delete a note.
     */
    public function actionDeleteNote()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();

        $id = craft()->request->getRequiredPost('id');

        $result = craft()->amForms_notes->deleteNoteById($id);
        $this->returnJson(array('success' => $result));
    }
}
