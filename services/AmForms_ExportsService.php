<?php
namespace Craft;

/**
 * AmForms - Exports service
 */
class AmForms_ExportsService extends BaseApplicationComponent
{
    private $_delimiter;
    private $_exportFiles = array();
    private $_exportFields = array();
    private $_exportColumns = array();
    private $_exportSpaceCounter = array();

    public function __construct()
    {
        $this->_delimiter = craft()->amForms_settings->getSettingsValueByHandleAndType('delimiter', AmFormsModel::SettingExport, ';');
    }

    /**
     * Get all exports.
     *
     * @return array|null
     */
    public function getAllExports()
    {
        $exportRecords = AmForms_ExportRecord::model()->ordered()->findAll();
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
     * Get export fields for a form.
     *
     * @param AmForms_FormModel $form
     *
     * @return array
     */
    public function getExportFields(AmForms_FormModel $form)
    {
        // Standard fields
        $exportFields = array(
            'id' => array(
                'id' => 'id',
                'handle' => 'id',
                'name' => Craft::t('id'),
                'checked' => 0,
                'type' => 'PlainText'
            ),
            'title' => array(
                'id' => 'title',
                'handle' => 'title',
                'name' => Craft::t('Title'),
                'checked' => 1,
                'type' => 'PlainText'
            ),
            'dateCreated' => array(
                'id' => 'dateCreated',
                'handle' => 'dateCreated',
                'name' => Craft::t('Date created'),
                'checked' => 0,
                'type' => 'Date'
            ),
            'dateUpdated' => array(
                'id' => 'dateUpdated',
                'handle' => 'dateUpdated',
                'name' => Craft::t('Date updated'),
                'checked' => 0,
                'type' => 'Date'
            ),
            'submittedFrom' => array(
                'id' => 'submittedFrom',
                'handle' => 'submittedFrom',
                'name' => Craft::t('Submitted from'),
                'checked' => 0,
                'type' => 'PlainText'
            )
        );

        // Get fieldlayout fields
        foreach ($form->getFieldLayout()->getTabs() as $tab) {
            // Tab fields
            $fields = $tab->getFields();
            foreach ($fields as $layoutField) {
                // Get actual field
                $field = $layoutField->getField();

                // Add to fields
                $exportFields[$field->handle] = $field;
            }
        }

        return $exportFields;
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
        if ($isNewExport) {
            // Do we need to get the total submissions to export?
            if (! $export->submissions && ! $export->startRightAway) {
                // Set total records to export
                $export->total = craft()->db->createCommand()
                                        ->select('COUNT(*)')
                                        ->from('amforms_submissions')
                                        ->where('formId=:formId', array(':formId' => $export->formId))
                                        ->queryScalar();
            }

            // We need to create an export file when we already have the submissions
            // Or when we have no manually given submissions and don't export right way
            if (! $export->startRightAway || $export->submissions) {
                // Create a new export file
                $export->file = $this->_createExportFile($export, $form);
            }
        }
        $exportRecord->setAttributes($export->getAttributes(), false);

        // Validate the attributes
        $exportRecord->validate();
        $export->addErrors($exportRecord->getErrors());

        if (! $export->hasErrors()) {
            if ($export->startRightAway) {
                // Get max power
                craft()->config->maxPowerCaptain();

                // Run the export!
                return $this->runExport($export);
            }
            else {
                // Save the export!
                $result = $exportRecord->save(false); // Skip validation now

                // Start export task?
                if ($result && $isNewExport) {
                    // Start task
                    $params = array(
                        'exportId'  => $exportRecord->id,
                        'batchSize' => craft()->amForms_settings->getSettingsValueByHandleAndType('exportRowsPerSet', AmFormsModel::SettingExport, 100)
                    );
                    craft()->tasks->createTask('AmForms_Export', Craft::t('{form} export', array('form' => $form->name)), $params);

                    // Notify user
                    craft()->userSession->setNotice(Craft::t('Export started.'));
                }

                return $result;
            }
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
        $files = IOHelper::getFiles($this->_getExportPath());
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
        if (! $export->submissions) {
            // Set total records to export
            $export->total = craft()->db->createCommand()
                            ->select('COUNT(*)')
                            ->from('amforms_submissions')
                            ->where('formId=:formId', array(':formId' => $export->formId))
                            ->queryScalar();
        }
        // Create a new export file
        $export->file = $this->_createExportFile($export, $form);

        // Save export and start export!
        if ($this->saveExport($export)) {
            // Start task
            $params = array(
                'exportId'  => $export->id,
                'batchSize' => craft()->amForms_settings->getSettingsValueByHandleAndType('exportRowsPerSet', AmFormsModel::SettingExport, 100)
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
    public function runExport(AmForms_ExportModel $export, $limit = null, $offset = null)
    {
        // Validate export file (if send by task)
        if (! IOHelper::fileExists($export->file) && ! $export->startRightAway) {
            return false;
        }

        // Get submissions
        $params = array(
            'formId' => $export->formId,
            'limit'  => $limit,
            'offset' => $offset
        );
        // Are there manually given submissions?
        if ($export->submissions) {
            $params['id'] = $export->submissions;
        }
        $criteria = craft()->amForms_submissions->getCriteria($params);
        $this->_addExportCriteria($export, $criteria);
        $submissions = $criteria->find();

        // Add submissions to export file
        if ($submissions && count($submissions) > 0) {
            // Get form
            $form = craft()->amForms_forms->getFormById($export->formId);
            if (! $form) {
                return false;
            }

            // Get field types
            $fields = $this->getExportFields($form);

            // Export submission to a zip file?
            if ($export->submissions) {
                // Add all fields
                $this->_exportFields[$export->id] = $fields;

                // Export submission
                foreach ($submissions as $submission) {
                    $this->_exportSubmissionToZip($export, $submission);
                }
            }
            else {
                // Get the export file
                if ($export->startRightAway) {
                    // Open output buffer
                    ob_start();

                    // Write to output stream
                    $this->_exportFiles['manual'] = fopen('php://output', 'w');

                    // Create columns
                    fputcsv($this->_exportFiles['manual'], $this->_getExportColumns($export, $form), $this->_delimiter);
                }
                else {
                    $this->_exportFiles[$export->id] = fopen($export->file, 'a');
                }

                // Get field handles and columns that should be included
                $columnCounter = 0;
                $this->_exportFields[$export->id] = array();
                foreach ($export->map['fields'] as $fieldHandle => $columnName) {
                    if ($export->map['included'][$fieldHandle] && isset($fields[$fieldHandle])) {
                        // Add field to export fields
                        $field = $fields[$fieldHandle];
                        if (is_array($field)) {
                            $field = (object) $field; // Fix standard fields
                        }
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
                fclose($this->_exportFiles[ ($export->startRightAway ? 'manual' : $export->id) ]);

                if ($export->startRightAway) {
                    // Close buffer and return data
                    $data = ob_get_clean();

                    // Use windows friendly newlines
                    $data = str_replace("\n", "\r\n", $data);

                    return $data;
                }
            }
        }

        return true;
    }

    /**
     * Get temporarily created export files.
     *
     * Note: these files were created by single submission export.
     *
     * @param array $exports Array with AmForms_ExportModel to be able to skip files.
     *
     * @return bool|array
     */
    public function getTempExportFiles($exports = array())
    {
        // Get exports folder path
        $folder = $this->_getExportPath();
        if (! is_dir($folder)) {
            return false;
        }

        // Gather files
        $tempFiles = array();
        $skipFiles = array();

        // Do we have any exports available?
        if (is_array($exports) && count($exports)) {
            foreach ($exports as $export) {
                $skipFiles[] = $export->file;
            }
        }

        // Find temp files
        $handle = opendir($folder);
        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..' || substr($file, 0, 1) == '.') {
                continue;
            }
            if (! in_array($folder . $file, $skipFiles)) {
                $tempFiles[] = $folder . $file;
            }
        }
        closedir($handle);

        // Return files if found any!
        return count($tempFiles) ? $tempFiles : false;
    }

    /**
     * Delete temporarily created export files.
     *
     * @return bool
     */
    public function deleteTempExportFiles()
    {
        // Get temp files
        $exports = $this->getAllExports();
        $files = $this->getTempExportFiles($exports);

        // Delete files
        if ($files) {
            foreach ($files as $file) {
                if (IOHelper::fileExists($file)) {
                    IOHelper::deleteFile($file);
                }
            }
            return true;
        }

        // We don't have any files to delete
        return false;
    }

    /**
     * Get export path.
     *
     * @return string
     */
    private function _getExportPath()
    {
        return craft()->path->getStoragePath() . 'amFormsExport/';
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
        $folder = $this->_getExportPath();
        IOHelper::ensureFolderExists($folder);

        // What type of export?
        $fileExtension = ($export->submissions) ? '.zip' : '.csv';

        // Create export file
        $file = $folder . $form->handle . $fileExtension;
        $counter = 1;
        while (! IOHelper::createFile($file)) {
            $file = $folder . $form->handle . $counter . $fileExtension;
            $counter ++;
        }

        // Only add columns when we are not working with a zip file
        if (! $export->submissions) {
            // Add columns to export file
            $exportFile = fopen($file, 'w');
            fputcsv($exportFile, $this->_getExportColumns($export, $form), $this->_delimiter);
            fclose($exportFile);
        }

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

        // Ignore Matrix fields in column name setting
        $ignoreMatrixName = craft()->amForms_settings->isSettingValueEnabled('ignoreMatrixFieldAndBlockNames', AmFormsModel::SettingExport);

        // Get fields
        $fields = $this->getExportFields($form);

        // Get column names
        foreach ($export->map['fields'] as $fieldHandle => $columnName) {
            // Should the field be included?
            if ($export->map['included'][$fieldHandle] && isset($fields[$fieldHandle])) {
                // Actual field
                $field = $fields[$fieldHandle];
                if (is_array($field)) {
                    $field = (object) $field; // Fix standard fields
                }

                // Add column based on the field type
                switch ($field->type) {
                    case 'Matrix':
                        $blockTypes = $field->getFieldType()->getSettings()->getBlockTypes();
                        foreach ($blockTypes as $blockType) {
                            $blockTypeFields = $blockType->getFields();

                            foreach ($blockTypeFields as $blockTypeField) {
                                $columns[] = (! $ignoreMatrixName ? $columnName . ':' . $blockType->name . ':' : '') . $blockTypeField->name;
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

        // Get fields
        $fields = $this->getExportFields($form);
        foreach ($fields as $field) {
            if (is_array($field)) {
                $field = (object) $field; // Fix standard fields
            }

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
     * @param mixed                   $submission
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
                    $fieldExportData = array();
                    foreach ($submission->$fieldHandle->find() as $fieldData) {
                        $fieldExportData[] = $fieldData->getUrl();
                    }
                    $data[] = implode(', ', $fieldExportData);
                    break;
                case 'Entries':
                    $fieldExportData = array();
                    foreach ($submission->$fieldHandle->find() as $fieldData) {
                        $fieldExportData[] = $fieldData->getContent()->title;
                    }
                    $data[] = implode(', ', $fieldExportData);
                    break;

                case 'Checkboxes':
                case 'MultiSelect':
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

                case 'Table':
                    if (isset($submission->$fieldHandle) && count($submission->$fieldHandle)) {
                        $fieldExportData = array();
                        foreach ($submission->$fieldHandle as $fieldData) {
                            foreach ($fieldData as $columnKey => $columnValue) {
                                if (substr($columnKey, 0, 3) == 'col' && $columnValue) {
                                    $fieldExportData[] = $columnValue;
                                }
                            }
                        }
                        $data[] = implode(', ', $fieldExportData);
                    }
                    else {
                        $data[] = '';
                    }
                    break;

                default:
                    $data[] = str_replace(array("\n", "\r", "\r\n", "\n\r"), ' ', $submission->$fieldHandle);
                    break;
            }

            $columnCounter ++;
        }

        // Either return the data or add to CSV
        if ($returnData) {
            return $data;
        }
        fputcsv($this->_exportFiles[ ($export->startRightAway ? 'manual' : $export->id) ], $data, $this->_delimiter);

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
                    fputcsv($this->_exportFiles[ ($export->startRightAway ? 'manual' : $export->id) ], $data, $this->_delimiter);
                }
            }
        }
    }

    /**
     * Export a submission to the export's zip file.
     *
     * @param AmForms_ExportModel     $export
     * @param AmForms_SubmissionModel $submission
     */
    private function _exportSubmissionToZip(AmForms_ExportModel $export, AmForms_SubmissionModel $submission)
    {
        // Get submission content
        $content = craft()->amForms_submissions->getSubmissionEmailBody($submission);

        // Create submission file
        $fileName = IOHelper::cleanFilename($submission->title);
        $fileExtension = '.html';
        $folder = $this->_getExportPath();
        $file = $folder . $fileName . $fileExtension;
        $counter = 1;
        while (! IOHelper::createFile($file)) {
            $file = $folder . $fileName . $counter . $fileExtension;
            $counter ++;
        }

        // Add content to file
        file_put_contents($file, $content);

        // Add file to zip
        Zip::add($export->file, $file, $folder);

        // Find possible assets
        foreach ($this->_exportFields[$export->id] as $fieldHandle => $field) {
            if (is_array($field)) {
                $field = (object) $field; // Fix standard fields
            }
            if ($field->type == 'Assets') {
                foreach ($submission->$fieldHandle->find() as $asset) {
                    $assetPath = craft()->amForms->getPathForAsset($asset);

                    if (IOHelper::fileExists($assetPath . $asset->filename)) {
                        Zip::add($export->file, $assetPath . $asset->filename, $assetPath);
                    }
                }
            }
        }

        // Remove submission file now that's in the zip
        IOHelper::deleteFile($file);
    }
}
