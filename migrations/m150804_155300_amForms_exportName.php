<?php
namespace Craft;

class m150804_155300_amForms_exportName extends BaseMigration
{
    public function safeUp()
    {
        $this->addColumnBefore('amforms_exports', 'name', array(ColumnType::Varchar), 'total');
        $this->addColumnAfter('amforms_exports', 'totalCriteria', array(ColumnType::Int), 'total');
    }
}
