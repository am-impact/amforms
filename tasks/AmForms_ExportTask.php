<?php
namespace Craft;

/**
 * AmForms - Export task
 */
class AmForms_ExportTask extends BaseTask
{
    private $_export;
    private $_totalSteps;

    /**
     * Defines the settings.
     *
     * @access protected
     * @return array
     */
    protected function defineSettings()
    {
        return array(
            'exportId' => AttributeType::Number,
            'batchSize' => AttributeType::Number
        );
    }

    /**
     * Returns the default description for this task.
     *
     * @return string
     */
    public function getDescription()
    {
        return 'Submissions export';
    }

    /**
     * Gets the total number of steps for this task.
     *
     * @return int
     */
    public function getTotalSteps()
    {
        if (! isset($this->_totalSteps)) {
            // Default
            $this->_totalSteps = 0;

            // Get export
            $export = craft()->amForms_exports->getExportById($this->getSettings()->exportId);
            if ($export) {
                $this->_export = $export;
                $this->_totalSteps = ceil($export->total / $this->getSettings()->batchSize);

                // No records, so it's already finished
                if ($this->_totalSteps == 0) {
                    $this->_export->finished = true;
                    craft()->amForms_exports->saveExport($this->_export);
                }
            }
        }

        return $this->_totalSteps;
    }

    /**
     * Runs a task step.
     *
     * Note: first step is 0!
     *
     * @param int $step
     *
     * @return bool
     */
    public function runStep($step)
    {
        craft()->config->maxPowerCaptain();
        craft()->config->set('cacheElementQueries', false);

        // Export settings
        $limit = $this->getSettings()->batchSize;
        $offset = ($step * $limit);

        // Start export
        $result = craft()->amForms_exports->runExport($this->_export, $limit, $offset);

        // Is the export finished?
        if (($step + 1) == $this->getTotalSteps()) {
            $this->_export->finished = true;
            craft()->amForms_exports->saveExport($this->_export);
        }

        return $result;
    }
}
