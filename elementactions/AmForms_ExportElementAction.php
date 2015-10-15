<?php
namespace Craft;

class AmForms_ExportElementAction extends BaseElementAction
{
    /**
     * @inheritDoc IComponentType::getName()
     *
     * @return string
     */
    public function getName()
    {
        return Craft::t('Export');
    }

    /**
     * @inheritDoc IElementAction::isDestructive()
     *
     * @return bool
     */
    public function isDestructive()
    {
        return true;
    }

    /**
     * @inheritDoc IElementAction::getConfirmationMessage()
     *
     * @return string|null
     */
    public function getConfirmationMessage()
    {
        return Craft::t('Are you sure you want to export the selected submissions?');
    }

    /**
     * @inheritDoc IElementAction::performAction()
     *
     * @param ElementCriteriaModel $criteria
     *
     * @return bool
     */
    public function performAction(ElementCriteriaModel $criteria)
    {
        // Get all submission
        $submissions = $criteria->find();

        // Gather submissions based on form
        $formSubmissions = array();
        foreach ($submissions as $submission) {
            if (! isset($formSubmissions[$submission->formId])) {
                $formSubmissions[$submission->formId] = array();
            }
            $formSubmissions[$submission->formId][] = $submission->id;
        }

        // Export submission(s)
        foreach ($formSubmissions as $formId => $submissionIds) {
            $total = count($submissionIds);

            $export = new AmForms_ExportModel();
            $export->name = Craft::t('{total} submission(s)', array('total' => $total));
            $export->formId = $formId;
            $export->total = $total;
            $export->totalCriteria = $total;
            $export->submissions = $submissionIds;
            craft()->amForms_exports->saveExport($export);
        }

        // Success!
        $this->setMessage(Craft::t('Submissions exported.'));
        return true;
    }
}
