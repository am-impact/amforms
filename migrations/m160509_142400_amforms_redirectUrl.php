<?php

namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName.
 */
class m160509_142400_amforms_redirectUrl extends BaseMigration
{
    /**
     * Any migration code in here is wrapped inside of a transaction.
     *
     * @return bool
     */
    public function safeUp()
    {
        $this->addColumnAfter('amforms_forms', 'redirectUrl', array(AttributeType::String), 'displayTabTitles');

        return true;
    }
}
