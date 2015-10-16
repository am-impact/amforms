<?php
namespace Craft;

/**
 * AmForms - Exports controller
 */
class AmForms_ExportsController extends BaseController
{
    /**
     * Make sure the current has access.
     */
    public function __construct()
    {
        $user = craft()->userSession->getUser();
        if (! $user->can('accessAmFormsExports')) {
            throw new HttpException(403, Craft::t('This action may only be performed by users with the proper permissions.'));
        }
    }

    /**
     * Show exports.
     */
    public function actionIndex()
    {
        $variables = array(
            'exports' => craft()->amForms_exports->getAllExports()
        );
        $this->renderTemplate('amForms/exports/index', $variables);
    }

    /**
     * Create or edit an export.
     *
     * @param array $variables
     */
    public function actionEditExport(array $variables = array())
    {
        // Do we have an export model?
        if (! isset($variables['export'])) {
            // Get export if available
            if (! empty($variables['exportId'])) {
                $variables['export'] = craft()->amForms_exports->getExportById($variables['exportId']);

                if (! $variables['export']) {
                    throw new Exception(Craft::t('No export exists with the ID “{id}”.', array('id' => $variables['exportId'])));
                }
            }
            else {
                $variables['export'] = new AmForms_ExportModel();
            }
        }

        // Get available forms
        $variables['availableForms'] = craft()->amForms_forms->getAllForms();
        if ($variables['availableForms']) {
            foreach ($variables['availableForms'] as $form) {
                $variables['fields'][$form->handle] = craft()->amForms_exports->getExportFields($form);
            }
        }

        // Render template!
        $this->renderTemplate('amforms/exports/_edit', $variables);
    }

    /**
     * Save an export.
     */
    public function actionSaveExport()
    {
        $this->requirePostRequest();

        // Get export if available
        $exportId = craft()->request->getPost('exportId');
        if ($exportId) {
            $export = craft()->amForms_exports->getExportById($exportId);

            if (! $export) {
                throw new Exception(Craft::t('No export exists with the ID “{id}”.', array('id' => $exportId)));
            }
        }
        else {
            $export = new AmForms_ExportModel();
        }

        // Get the chosen form
        $export->formId = craft()->request->getPost('formId');

        // Get proper POST attributes
        $mapping = craft()->request->getPost($export->formId);
        $criteria = isset($mapping['criteria']) ? $mapping['criteria'] : null;
        if ($criteria) {
            // Remove criteria from mapping
            unset($mapping['criteria']);

            // Get criteria field IDs
            foreach ($criteria['fields'] as $key => $field) {
                $splittedField = explode('-', $field);
                $criteria['fields'][$key] = $splittedField[ (count($splittedField) - 1) ];
            }

            // Fix fields that work by the criteriaCounter
            // We might've deleted a criteria row, so we have to make sure the rows are corrected
            foreach ($criteria['fields'] as $key => $field) {
                if (! isset($criteria[$field][$key])) {
                    foreach ($criteria[$field] as $subKey => $subValues) {
                        if ($subKey > $key) {
                            $criteria[$field][$key] = $criteria[$field][$subKey];
                            unset($criteria[$field][$subKey]);
                            break;
                        }
                    }
                }
            }

            // Remove unnecessary criteria
            foreach ($criteria as $fieldId => $values) {
                if (is_numeric($fieldId) && ! in_array($fieldId, $criteria['fields'])) {
                    unset($criteria[$fieldId]);
                }
            }
        }

        // Export attributes
        $export->name = craft()->request->getPost('name');
        $export->totalCriteria = null; // Reset total that met the current criteria
        $export->map = $mapping;
        $export->criteria = $criteria;
        $export->startRightAway = ! (bool) craft()->request->getPost('save', false);

        // Save export
        $result = craft()->amForms_exports->saveExport($export);
        if ($result) {
            if ($export->startRightAway) {
                craft()->request->sendFile('export.csv', $result, array('forceDownload' => true, 'mimeType' => 'text/csv'));
            }
            else {
                craft()->userSession->setNotice(Craft::t('Export saved.'));

                $this->redirectToPostedUrl($export);
            }
        }
        else {
            $message = $export->startRightAway ? 'No submissions exists (with given criteria).' : 'Couldn’t save export.';
            craft()->userSession->setError(Craft::t($message));

            // Send the export back to the template
            craft()->urlManager->setRouteVariables(array(
                'export' => $export
            ));
        }
    }

    /**
     * Delete an export.
     */
    public function actionDeleteExport()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();

        $id = craft()->request->getRequiredPost('id');

        $result = craft()->amForms_exports->deleteExportById($id);
        $this->returnJson(array('success' => $result));
    }

    /**
     * Restart an export.
     */
    public function actionRestartExport()
    {
        // Find export ID
        $exportId = craft()->request->getParam('id');
        if (! $exportId) {
            $this->redirect('amforms/exports');
        }

        // Get the export
        $export = craft()->amForms_exports->getExportById($exportId);
        if (! $export) {
            throw new Exception(Craft::t('No export exists with the ID “{id}”.', array('id' => $exportId)));
        }

        // Restart export
        craft()->amForms_exports->restartExport($export);

        // Redirect
        $this->redirect('amforms/exports');
    }

    /**
     * Download an export.
     */
    public function actionDownloadExport()
    {
        // Find export ID
        $exportId = craft()->request->getParam('id');
        if (! $exportId) {
            $this->redirect('amforms/exports');
        }

        // Get the export
        $export = craft()->amForms_exports->getExportById($exportId);
        if (! $export) {
            throw new Exception(Craft::t('No export exists with the ID “{id}”.', array('id' => $exportId)));
        }

        // Is the export finished and do we have a file?
        if (! $export->finished || ! IOHelper::fileExists($export->file)) {
            $this->redirect('amforms/exports');
        }

        // Download file!
        $this->_downloadFile($export);
    }

    /**
     * Export a submission.
     */
    public function actionExportSubmission()
    {
        $this->requirePostRequest();

        // Get the submission
        $submissionId = craft()->request->getRequiredPost('submissionId');
        $submission = craft()->amForms_submissions->getSubmissionById($submissionId);
        if (! $submission) {
            throw new Exception(Craft::t('No submission exists with the ID “{id}”.', array('id' => $submissionId)));
        }

        // Delete temporarily files from previous single submission exports
        craft()->amForms_exports->deleteTempExportFiles();

        // Export submission
        $export = new AmForms_ExportModel();
        $export->name = Craft::t('{total} submission(s)', array('total' => 1));
        $export->formId = $submission->formId;
        $export->total = 1;
        $export->totalCriteria = 1;
        $export->submissions = array($submissionId);
        $export->startRightAway = true;
        $result = craft()->amForms_exports->saveExport($export);

        if ($result) {
            $this->_downloadFile($export);
        }
        else {
            $this->redirectToPostedUrl($submission);
        }
    }

    /**
     * Get a criteria row.
     */
    public function actionGetCriteria()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();

        $return = array(
            'success' => false
        );

        // Get required POST data
        $formId = craft()->request->getRequiredPost('formId');
        $counter = craft()->request->getRequiredPost('counter');

        // Get the form
        $form = craft()->amForms_forms->getFormById($formId);

        if ($form) {
            // Get form fields
            $fields = craft()->amForms_exports->getExportFields($form);

            // Get HTML
            $variables = array(
                'form' => $form,
                'fields' => $fields,
                'criteriaCounter' => $counter
            );
            $html = craft()->templates->render('amForms/exports/_fields/template', $variables, true);

            $return = array(
                'success' => true,
                'row' => $html,
                'headHtml' => craft()->templates->getHeadHtml(),
                'footHtml' => craft()->templates->getFootHtml()
            );
        }

        $this->returnJson($return);
    }

    /**
     * Get total submissions to export that meet the saved criteria.
     */
    public function actionGetTotalByCriteria()
    {
        // Find export ID
        $exportId = craft()->request->getParam('id');
        if (! $exportId) {
            $this->redirect('amforms/exports');
        }

        // Get the export
        $export = craft()->amForms_exports->getExportById($exportId);
        if (! $export) {
            throw new Exception(Craft::t('No export exists with the ID “{id}”.', array('id' => $exportId)));
        }

        // Get total submissions by criteria
        craft()->amForms_exports->saveTotalByCriteria($export);

        // Redirect to exports!
        $this->redirect('amforms/exports');
    }

    /**
     * Force an export file download.
     *
     * @param AmForms_ExportModel $export
     */
    private function _downloadFile(AmForms_ExportModel $export)
    {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . basename($export->file));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($export->file));
        readfile($export->file);
        die();
    }
}
