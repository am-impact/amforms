<?php
namespace Craft;

class AmForms_ExportModel extends BaseModel
{
    protected function defineAttributes()
    {
        return array(
            'id'       => AttributeType::Number,
            'formId'   => AttributeType::Number,
            'total'    => AttributeType::Number,
            'finished' => AttributeType::Bool,
            'file'     => AttributeType::String,
            'map'      => AttributeType::Mixed
        );
    }
}