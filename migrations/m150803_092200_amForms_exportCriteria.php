<?php
namespace Craft;

class m150803_092200_amForms_exportCriteria extends BaseMigration
{
    public function safeUp()
    {
        $this->addColumnAfter('amforms_exports', 'criteria', array(ColumnType::Text), 'map');
    }
}
