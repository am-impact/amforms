<?php
namespace Craft;

/**
 * AmForms - Exports service
 */
class AmForms_ExportsService extends BaseApplicationComponent
{
    private $_exportFiles = array();

    /**
     * Get all exports.
     *
     * @return array|null
     */
    public function getAllExports()
    {
        $exportRecords = AmForms_ExportRecord::model()->findAll();
        if ($exportRecords) {
            return AmForms_ExportModel::populateModels($exportRecords);
        }
        return null;
    }

    /**
     * Get an export by its ID.
     *
     * @param int $id
     *
     * @return AmForms_ExportModel|null
     */
    public function getExportById($id)
    {
        $exportRecord = AmForms_ExportRecord::model()->findById($id);
        if ($exportRecord) {
            return AmForms_ExportModel::populateModel($exportRecord);
        }
        return null;
    }

    /**
     * Save an export.
     *
     * @param AmForms_ExportModel $export
     *
     * @throws Exception
     * @return bool
     */
    public function saveExport(AmForms_ExportModel $export)
    {
        $isNewExport = ! $export->id;

        // Get the export record
        if ($export->id) {
            $exportRecord = AmForms_ExportRecord::model()->findById($export->id);

            if (! $exportRecord) {
                throw new Exception(Craft::t('No export exists with the ID “{id}”.', array('id' => $export->id)));
            }
        }
        else {
            $exportRecord = new AmForms_ExportRecord();
        }

        // Get the form
        $form = craft()->amForms_forms->getFormById($export->formId);
        if (! $form) {
            throw new Exception(Craft::t('No form exists with the ID “{id}”.', array('id' => $export->formId)));
        }

        // Export attributes
        $exportRecord->setAttributes($export->getAttributes(), false);
        if ($isNewExport) {
            // Set total records to export
            $exportRecord->total = craft()->db->createCommand()
                                    ->select('COUNT(*)')
                                    ->from('amforms_submissions')
                                    ->where('formId=:formId', array(':formId' => $export->formId))
                                    ->queryScalar();

            // Create a new export file
            $exportRecord->file = $this->_createExportFile($export, $form);
        }

        // Validate the attributes
        $exportRecord->validate();
        $export->addErrors($exportRecord->getErrors());

        if (! $export->hasErrors()) {
            // Save the export!
            $result = $exportRecord->save(false); // Skip validation now

            // Start export task?
            if ($result && $isNewExport) {
                // Start task
                $params = array(
                    'exportId'  => $exportRecord->id,
                    'batchSize' => 100
                );
                craft()->tasks->createTask('AmForms_Export', Craft::t('{form} export', array('form' => $form->name)), $params);

                // Notify user
                craft()->userSession->setNotice(Craft::t('Export started.'));
            }

            return $result;
        }

        return false;
    }

    /**
     * Delete an export.
     *
     * @param int $id
     *
     * @return bool
     */
    public function deleteExportById($id)
    {
        $export = $this->getExportById($id);
        if ($export) {
            IOHelper::deleteFile($export->file);
        }

        return craft()->db->createCommand()->delete('amforms_exports', array('id' => $id));
    }

    /**
     * Delete export files for a form.
     *
     * @param AmForms_FormModel $form
     *
     * @return bool
     */
    public function deleteExportFilesForForm(AmForms_FormModel $form)
    {
        $files = IOHelper::getFiles(craft()->path->getStoragePath() . 'amFormsExport/');
        if (! $files || ! count($files)) {
            return false;
        }

        foreach ($files as $file) {
            if (strpos($file, $form->handle) !== false) {
                IOHelper::deleteFile($file);
            }
        }
    }

    /**
     * Run an export.
     *
     * @param AmForms_ExportModel $export
     * @param int                 $limit
     * @param int                 $offset
     *
     * @return bool
     */
    public function runExport(AmForms_ExportModel $export, $limit, $offset)
    {
        // Validate export file
        if (! IOHelper::fileExists($export->file)) {
            return false;
        }

        // Get submissions
        $params = array(
            'formId' => $export->formId,
            'limit'  => $limit,
            'offset' => $offset
        );
        $criteria = craft()->amForms_submissions->getCriteria($params);
        $submissions = $criteria->find();

        // Add submissions to export file
        if ($submissions && count($submissions) > 0) {
            // Get the export file
            $this->_exportData($export, $submissions);
        }

        return true;
    }

    /**
     * Create an export file.
     *
     * @param AmForms_ExportModel $export
     * @param AmForms_FormModel   $form
     *
     * @return string
     */
    private function _createExportFile(AmForms_ExportModel $export, AmForms_FormModel $form)
    {
        // Determine folder
        $folder = craft()->path->getStoragePath() . 'amFormsExport/';
        IOHelper::ensureFolderExists($folder);

        // Create export file
        $file = $folder . $form->handle . '.csv';
        $counter = 1;
        while (! IOHelper::createFile($file)) {
            $file = $folder . $form->handle . $counter . '.csv';
            $counter ++;
        }

        // Add columns to export file
        $exportFile = fopen($file, 'w');
        fputcsv($exportFile, $this->_getExportColumns($export, $form));
        fclose($exportFile);

        // Return file path
        return $file;
    }

    /**
     * Get export columns.
     *
     * @param AmForms_ExportModel $export
     * @param AmForms_FormModel   $form
     *
     * @return array
     */
    private function _getExportColumns(AmForms_ExportModel $export, AmForms_FormModel $form)
    {
        $columns = array();
        $fieldLayout = $form->getFieldLayout();
        foreach ($fieldLayout->getFields() as $fieldLayoutField) {
            $field = $fieldLayoutField->getField();

            // Should the field be included?
            if ($export->map['included'][$field->handle]) {
                // Dynamic column name
                $columnName = $export->map['fields'][$field->handle];

                // Add column based on the field type
                switch ($field->type) {
                    case 'Matrix':
                        $blockTypes = $field->getFieldType()->getSettings()->getBlockTypes();
                        foreach ($blockTypes as $blockType) {
                            $blockTypeFields = $blockType->getFields();

                            foreach ($blockTypeFields as $blockTypeField) {
                                $columns[] = $columnName . ':' . $blockType->name . ':' . $blockTypeField->name;
                            }
                        }
                        break;

                    default:
                        $columns[] = $columnName;
                        break;
                }
            }
        }
        return $columns;
    }

    /**
     * Export data.
     *
     * @param AmForms_ExportModel $export
     * @param array               $submissions
     */
    private function _exportData(AmForms_ExportModel $export, $submissions)
    {
        $this->_exportFiles[$export->id] = fopen($export->file, 'a');

        $rowCounter = 0;
        $exportData = array();

        // Get field handles that should be included
        $includedFields = array();
        foreach ($export->map['fields'] as $fieldHandle => $columnName) {
            if ($export->map['included'][$fieldHandle]) {
                $includedFields[] = $fieldHandle;
            }
        }

        // Get submission's data
        foreach ($submissions as $submission) {
            // @TODO Ask brandon wtf is going on here
            // For some reason we don't have the proper content without this
            $submission->setContent(craft()->content->getContent($submission));
            $attributes = $this->_getAttributesForModel($submission);

            // Multiple rows data
            $hasMoreRows = false;
            $moreRowsData = array();

            // This row's data
            $data = array();
            foreach ($includedFields as $columnCounter => $fieldHandle) {
                // Get the attribute value
                $attribute = isset($attributes[$fieldHandle]) ? $attributes[$fieldHandle] : false;

                // Do we have one?
                if ($attribute) {
                    // Multiple values?
                    if (is_array($attribute)) {
                        // More than one?
                        if (count($attribute) > 1) {
                            $hasMoreRows = true;
                            $moreRowsData[$columnCounter] = array_slice($attribute, 1);
                            // Add the first row and add the others later
                            foreach ($attribute[0] as $key => $attributeValue) {
                                $data[] = $attributeValue;
                            }
                        }
                        // Or just one additional row?
                        elseif (count($attribute) == 1) {
                            foreach ($attribute[0] as $key => $attributeValue) {
                                $data[] = $attributeValue;
                            }
                        }
                    }
                    else {
                        $data[] = $attribute;
                    }
                }
            }

            // Add row to CSV
            $exportData[$rowCounter] = $data;
            fputcsv($this->_exportFiles[$export->id], $data);

            // Add more rows?
            if ($hasMoreRows) {
                foreach ($moreRowsData as $columnCounter => $rows) {
                    foreach ($rows as $row) {
                        // This row's data
                        $data = array();

                        // Add old data for other fields and new for the multiple rows
                        for ($i = 0; $i < count($includedFields); $i++) {
                            if ($i == $columnCounter) {
                                foreach ($row as $rowValue) {
                                    $data[] = $rowValue;
                                }
                            }
                            else {
                                $data[] = $exportData[$rowCounter][$i];
                            }
                        }

                        // Add row to CSV
                        fputcsv($this->_exportFiles[$export->id], $data);
                    }
                }
            }

            $rowCounter ++;
        }

        fclose($this->_exportFiles[$export->id]);
    }

    /**
     * Get attributes for a model.
     *
     * @param AmForm_SubmissionModel/MatrixBlockModel $model
     *
     * @return array
     */
    private function _getAttributesForModel($model)
    {
        $attributes  = array();
        $content     = $model->getContent()->getAttributes();
        $fieldLayout = $model->getFieldLayout();
        foreach ($fieldLayout->getFields() as $fieldLayoutField) {
            $field = $fieldLayoutField->getField();

            switch ($field->type) {
                case 'Assets':
                case 'Entries':
                    $attributes[$field->handle] = array();
                    foreach ($model->{$field->handle}->find() as $fieldData) {
                        $attributes[$field->handle][] = $fieldData->getContent()->title;
                    }
                    $attributes[$field->handle] = implode(', ', $attributes[$field->handle]);
                    break;

                case 'Lightswitch':
                    if (isset($content[$field->handle])) {
                        $attributes[$field->handle] = $content[$field->handle] ? Craft::t('Yes') : Craft::t('No');
                    }
                    break;

                case 'Matrix':
                    $attributes[$field->handle] = array();
                    foreach ($model->{$field->handle}->find() as $matrixBlock) {
                        $attributes[$field->handle][] = $this->_getAttributesForModel($matrixBlock);
                    }
                    break;

                default:
                    if (isset($content[$field->handle])) {
                        $attributes[$field->handle] = $content[$field->handle];
                    }
                    break;
            }
        }
        return $attributes;
    }
}