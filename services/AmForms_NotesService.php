<?php
namespace Craft;

/**
 * AmForms - Notes service
 */
class AmForms_NotesService extends BaseApplicationComponent
{
    /**
     * Get a note by its ID.
     *
     * @param int $id
     *
     * @return AmForms_NoteModel|null
     */
    public function getNoteById($id)
    {
        $noteRecord = AmForms_NoteRecord::model()->findById($id);
        if ($noteRecord) {
            return AmForms_NoteModel::populateModel($noteRecord);
        }
        return null;
    }

    /**
     * Get notes by submission ID.
     *
     * @param int $id
     *
     * @return array
     */
    public function getNotesBySubmissionId($id)
    {
        $notes = AmForms_NoteRecord::model()->ordered()->findAllByAttributes(array('submissionId' => $id));
        if ($notes) {
            return AmForms_NoteModel::populateModels($notes);
        }
        return null;
    }

    /**
     * Save a note.
     *
     * @param AmForms_NoteModel $note
     *
     * @throws Exception
     * @return bool
     */
    public function saveNote(AmForms_NoteModel $note)
    {
        // Get the note record
        if ($note->id) {
            $noteRecord = AmForms_NoteRecord::model()->findById($note->id);

            if (! $noteRecord) {
                throw new Exception(Craft::t('No note exists with the ID “{id}”.', array('id' => $note->id)));
            }
        }
        else {
            $noteRecord = new AmForms_NoteRecord();
        }

        // Note attributes
        $noteRecord->setAttributes($note->getAttributes(), false);

        // Validate the attributes
        $noteRecord->validate();
        $note->addErrors($noteRecord->getErrors());

        if (! $note->hasErrors()) {
            // Save the note!
            return $noteRecord->save(false); // Skip validation now
        }

        return false;
    }

    /**
     * Delete a note.
     *
     * @param int $id
     *
     * @return bool
     */
    public function deleteNoteById($id)
    {
        return craft()->db->createCommand()->delete('amforms_notes', array('id' => $id));
    }
}
