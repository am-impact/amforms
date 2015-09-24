<?php
namespace Craft;

class m150924_123000_amForms_addAssetsToNotification extends BaseMigration
{
    public function safeUp()
    {
        $this->addColumnAfter('amforms_forms', 'notificationFilesEnabled', array(ColumnType::TinyInt, 'length' => 1, 'unsigned' => true, 'default' => 0, 'null' => false), 'notificationEnabled');
    }
}
