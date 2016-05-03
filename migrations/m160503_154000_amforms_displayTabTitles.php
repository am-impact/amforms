<?php

namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName.
 */
class m160503_154000_amforms_displayTabTitles extends BaseMigration
{
    /**
     * Any migration code in here is wrapped inside of a transaction.
     *
     * @return bool
     */
    public function safeUp()
    {
        $this->addColumnAfter('amforms_forms', 'displayTabTitles', array(AttributeType::Bool, 'default' => false), 'submissionEnabled');

        return true;
    }
}
