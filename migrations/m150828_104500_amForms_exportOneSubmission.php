<?php
namespace Craft;

class m150828_104500_amForms_exportOneSubmission extends BaseMigration
{
    public function safeUp()
    {
        // Add submissions column
        $this->addColumnAfter('amforms_exports', 'submissions', array(ColumnType::Text), 'criteria');
    }
}
