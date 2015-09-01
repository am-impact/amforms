<?php
namespace Craft;

class AmForms_ExportModel extends BaseModel
{
    protected function defineAttributes()
    {
        return array(
            'id'             => AttributeType::Number,
            'formId'         => AttributeType::Number,
            'name'           => AttributeType::String,
            'total'          => AttributeType::Number,
            'totalCriteria'  => AttributeType::Number,
            'finished'       => AttributeType::Bool,
            'file'           => AttributeType::String,
            'map'            => AttributeType::Mixed,
            'criteria'       => AttributeType::Mixed,
            'submissions'    => AttributeType::Mixed,
            'dateUpdated'    => AttributeType::DateTime,

            // Start export immediately?
            'startRightAway' => array(AttributeType::Bool, 'default' => false)
        );
    }
}
