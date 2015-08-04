<?php
namespace Craft;

/**
 * AmForms - Exports service
 */
class AmForms_ExportsService extends BaseApplicationComponent
{
    private $_exportFiles = array();
    private $_exportFields = array();
    private $_exportColumns = array();
    private $_exportSpaceCounter = array();

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
                    'batchSize' => craft()->amForms_settings->getSettingsValueByHandleAndType('exportRowsPerSet', AmFormsModel::SettingGeneral, 100)
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
     * Restart an export.
     *
     * @param AmForms_ExportModel $export
     */
    public function restartExport(AmForms_ExportModel $export)
    {
        // Get the form
        $form = craft()->amForms_forms->getFormById($export->formId);
        if (! $form) {
            throw new Exception(Craft::t('No form exists with the ID “{id}”.', array('id' => $export->formId)));
        }

        // Delete old export
        if (IOHelper::fileExists($export->file)) {
            IOHelper::deleteFile($export->file);
        }

        // Reset finished
        $export->finished = false;
        // Set total records to export
        $export->total = craft()->db->createCommand()
                        ->select('COUNT(*)')
                        ->from('amforms_submissions')
                        ->where('formId=:formId', array(':formId' => $export->formId))
                        ->queryScalar();
        // Create a new export file
        $export->file = $this->_createExportFile($export, $form);

        // Save export and start export!
        if ($this->saveExport($export)) {
            // Start task
            $params = array(
                'exportId'  => $export->id,
                'batchSize' => craft()->amForms_settings->getSettingsValueByHandleAndType('exportRowsPerSet', AmFormsModel::SettingGeneral, 100)
            );
            craft()->tasks->createTask('AmForms_Export', Craft::t('{form} export', array('form' => $form->name)), $params);

            // Notify user
            craft()->userSession->setNotice(Craft::t('Export started.'));
        }
    }

    /**
     * Save total submissions that meet the saved criteria.
     *
     * @param AmForms_ExportModel $export
     */
    public function saveTotalByCriteria(AmForms_ExportModel $export)
    {
        // Set submissions criteria
        $params = array(
            'limit' => null,
            'formId' => $export->formId
        );
        $criteria = craft()->amForms_submissions->getCriteria($params);

        // Add export criteria
        $this->_addExportCriteria($export, $criteria);

        // Get total!
        $export->totalCriteria = $criteria->total();

        // Save export!
        $this->saveExport($export);
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
        $this->_addExportCriteria($export, $criteria);
        $submissions = $criteria->find();

        // Add submissions to export file
        if ($submissions && count($submissions) > 0) {
            // Get the export file
            $this->_exportFiles[$export->id] = fopen($export->file, 'a');

            // Get field types
            $fields = array();
            $fieldLayout = $submissions[0]->getFieldLayout(); // We just need a model
            foreach ($fieldLayout->getFields() as $fieldLayoutField) {
                $field = $fieldLayoutField->getField();

                // Add field type
                $fields[$field->handle] = $field;
            }

            // Get field handles and columns that should be included
            $columnCounter = 0;
            $this->_exportFields[$export->id] = array();
            foreach ($export->map['fields'] as $fieldHandle => $columnName) {
                if ($export->map['included'][$fieldHandle] && isset($fields[$fieldHandle])) {
                    // Add field to export fields
                    $field = $fields[$fieldHandle];
                    $this->_exportFields[$export->id][$fieldHandle] = $field;

                    // Remember how much space this field is taking
                    $spaceCounter = 0;

                    // Add column so we know where to place the data later
                    switch ($field->type) {
                        case 'Matrix':
                            $blockTypes = $field->getFieldType()->getSettings()->getBlockTypes();
                            foreach ($blockTypes as $blockType) {
                                $blockTypeFields = $blockType->getFields();

                                $this->_exportColumns[$export->id][$field->handle . ':' . $blockType->handle] = $columnCounter;

                                $columnCounter += count($blockTypeFields);

                                $spaceCounter += count($blockTypeFields);
                            }
                            break;

                        default:
                            $this->_exportColumns[$export->id][$field->handle] = $columnCounter;

                            $spaceCounter ++;
                            break;
                    }

                    $columnCounter ++;

                    $this->_exportSpaceCounter[$export->id][$field->handle] = $spaceCounter;
                }
            }

            // Export submission model
            foreach ($submissions as $submission) {
                 $this->_exportSubmission($export, $submission);
            }

            // Close export file
            fclose($this->_exportFiles[$export->id]);
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
        $fields = array();
        $columns = array();

        // Get field layout
        $fieldLayout = $form->getFieldLayout();
        foreach ($fieldLayout->getFields() as $fieldLayoutField) {
            $field = $fieldLayoutField->getField();
            $fields[$field->handle] = $field;
        }

        // Get column names
        foreach ($export->map['fields'] as $fieldHandle => $columnName) {
            // Should the field be included?
            if ($export->map['included'][$fieldHandle] && isset($fields[$fieldHandle])) {
                // Actual field
                $field = $fields[$fieldHandle];

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
     * Add export criteria.
     *
     * @param AmForms_ExportModel  $export
     * @param ElementCriteriaModel &$criteria
     *
     * @return bool
     */
    private function _addExportCriteria(AmForms_ExportModel $export, &$criteria)
    {
        // Do we even have criteria?
        if (! $export->criteria) {
            return false;
        }

        // Get form
        $form = craft()->amForms_forms->getFormById($export->formId);
        if (! $form) {
            return false;
        }

        // Gather related criteria
        $relatedTo = array('or');

        // Get fieldlayout
        $fieldLayout = $form->getFieldLayout();
        foreach ($fieldLayout->getFields() as $fieldLayoutField) {
            $field = $fieldLayoutField->getField();

            // Is field set in criteria?
            if (! isset($export->criteria[ $field->id ])) {
                continue;
            }

            // Add criteria based on field type
            switch ($field->type) {
                case 'Assets':
                case 'Entries':
                    foreach ($export->criteria[ $field->id ] as $criteriaValue) {
                        if (! empty($criteriaValue) && is_array($criteriaValue) && count($criteriaValue)) {
                            $relatedTo[] = $criteriaValue[0];
                        }
                    }
                    break;

                case 'Checkboxes':
                    $setCriteria = array('or');
                    foreach ($export->criteria[ $field->id ] as $criteriaValue) {
                        if (! empty($criteriaValue)) {
                            foreach ($criteriaValue as $subCriteriaValue) {
                                $setCriteria[] = '*"' . $subCriteriaValue . '"*';
                            }
                        }
                    }
                    $criteria->{$field->handle} = $setCriteria;
                    break;

                case 'Lightswitch':
                    $valueFound = false;
                    foreach ($export->criteria[ $field->id ] as $criteriaValue) {
                        if (! empty($criteriaValue)) {
                            $valueFound = true;
                            $criteria->{$field->handle} = $criteriaValue;
                        }
                    }
                    if (! $valueFound) {
                        $criteria->{$field->handle} = 'not 1';
                    }
                    break;

                case 'Dropdown':
                case 'PlainText':
                case 'RadioButtons':
                    $setCriteria = array('or');
                    foreach ($export->criteria[ $field->id ] as $criteriaValue) {
                        if (! empty($criteriaValue)) {
                            $setCriteria[] = $criteriaValue;
                        }
                    }
                    $criteria->{$field->handle} = $setCriteria;
                    break;
            }
        }

        // Set relations criteria
        if (count($relatedTo) > 1) {
            $criteria->relatedTo = $relatedTo;
        }
    }

    /**
     * Export submission.
     *
     * @param AmForms_ExportModel     $export
     * @param AmForms_SubmissionModel $submission
     * @param bool                    $returnData
     */
    private function _exportSubmission(AmForms_ExportModel $export, $submission, $returnData = false)
    {
        // Row data
        $data = array();
        $columnCounter = 0;

        // Multiple rows data
        $hasMoreRows = false;
        $moreRowsData = array();

        if ($returnData) {
            $fields = array();
            $fieldLayout = $submission->getFieldLayout();
            foreach ($fieldLayout->getFields() as $fieldLayoutField) {
                $field = $fieldLayoutField->getField();
                $fields[$field->handle] = $field;
            }
        }
        else {
            $fields = $this->_exportFields[$export->id];
        }

        foreach ($fields as $fieldHandle => $field) {
            switch ($field->type) {
                case 'Assets':
                case 'Entries':
                    $fieldExportData = array();
                    foreach ($submission->$fieldHandle->find() as $fieldData) {
                        $fieldExportData[] = $fieldData->getContent()->title;
                    }
                    $data[] = implode(', ', $fieldExportData);
                    break;

                case 'Checkboxes':
                    if (isset($submission->$fieldHandle) && count($submission->$fieldHandle)) {
                        $fieldExportData = array();
                        foreach ($submission->$fieldHandle as $fieldData) {
                            $fieldExportData[] = $fieldData->value;
                        }
                        $data[] = implode(', ', $fieldExportData);
                    }
                    else {
                        $data[] = '';
                    }
                    break;

                case 'Lightswitch':
                    $data[] = $submission->$fieldHandle ? Craft::t('Yes') : Craft::t('No');
                    break;

                case 'Matrix':
                    $blockCounter = 0;
                    $matrixBlocks = $submission->$fieldHandle->find();
                    if (! $matrixBlocks) {
                        // No matrix data, so we have to add empty cells!
                        for ($i = 1; $i <= $this->_exportSpaceCounter[$export->id][$fieldHandle]; $i++) {
                            $data[] = '';
                        }
                    }
                    else {
                        foreach ($matrixBlocks as $matrixBlock) {
                            $matrixBlockType = $matrixBlock->getType();
                            $blockData = $this->_exportSubmission($export, $matrixBlock, true);

                            // Column counter
                            $startFrom = $this->_exportColumns[$export->id][$fieldHandle . ':' . $matrixBlockType->handle];

                            // Multiple blocks?
                            if (count($matrixBlocks) > 1 && $blockCounter > 0) {
                                $hasMoreRows = true;
                                $moreRowsData[$startFrom][] = $blockData;
                            }
                            else {
                                // Empty cells till we've reached the block type
                                for ($i = 0; $i < ($startFrom - $columnCounter); $i++) {
                                    $data[] = '';
                                }
                                // We just have one block or we are adding the first block
                                $spaceCounter = 0;
                                foreach ($blockData as $blockValue) {
                                    $data[] = $blockValue;
                                    $spaceCounter ++;
                                }
                                // Empty cells till we've reached the next field, if necessary
                                if ($startFrom == $columnCounter) {
                                    for ($i = 0; $i < ($this->_exportSpaceCounter[$export->id][$fieldHandle] - $spaceCounter); $i++) {
                                        $data[] = '';
                                    }
                                }
                            }

                            $blockCounter ++;
                        }
                    }
                    break;

                default:
                    $data[] = $submission->$fieldHandle;
                    break;
            }

            $columnCounter ++;
        }

        // Either return the data or add to CSV
        if ($returnData) {
            return $data;
        }
        fputcsv($this->_exportFiles[$export->id], $data);

        // Add more rows?
        if ($hasMoreRows) {
            foreach ($moreRowsData as $columnCounter => $rows) {
                foreach ($rows as $row) {
                    // This row's data
                    $data = array();

                    // Empty cells till we've reached the data
                    for ($i = 0; $i < $columnCounter; $i++) {
                        $data[] = '';
                    }
                    // Add row data
                    foreach ($row as $rowData) {
                        $data[] = $rowData;
                    }

                    // Add row to CSV
                    fputcsv($this->_exportFiles[$export->id], $data);
                }
            }
        }
    }
}
