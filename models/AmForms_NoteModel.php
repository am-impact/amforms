<?php
namespace Craft;

class AmForms_NoteModel extends BaseModel
{
    protected function defineAttributes()
    {
        return array(
            'id'           => AttributeType::Number,
            'submissionId' => AttributeType::Number,
            'name'         => AttributeType::String,
            'text'         => AttributeType::Mixed,
            'dateCreated'  => AttributeType::DateTime
        );
    }
}
