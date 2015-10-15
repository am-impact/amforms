<?php

namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName.
 */
class m151015_114827_amforms_addSendCopyToForm extends BaseMigration
{
    /**
     * Any migration code in here is wrapped inside of a transaction.
     *
     * @return bool
     */
    public function safeUp()
    {
        return $this->addColumnAfter('amforms_forms', 'sendCopy', array(AttributeType::Bool, 'default' => false), 'submissionEnabled');
    }
}
