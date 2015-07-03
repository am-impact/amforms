<?php
namespace Craft;

/**
 * AmForms - Exports controller
 */
class AmForms_ExportsController extends BaseController
{
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

        // Export attributes
        $export->formId = craft()->request->getPost('formId');
        $export->map = craft()->request->getPost($export->formId);

        // Save export
        if (craft()->amForms_exports->saveExport($export)) {
            craft()->userSession->setNotice(Craft::t('Export saved.'));

            $this->redirectToPostedUrl($export);
        }
        else {
            craft()->userSession->setError(Craft::t('Couldn’t save export.'));

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
}