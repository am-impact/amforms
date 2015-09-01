<?php
namespace Craft;

class AmForms_ExportRecord extends BaseRecord
{
    /**
     * Return table name
     *
     * @return string
     */
    public function getTableName()
    {
        return 'amforms_exports';
    }

    /**
     * Define attributes
     *
     * @return array
     */
    public function defineAttributes()
    {
        return array(
            'name'          => AttributeType::String,
            'total'         => array(AttributeType::Number, 'default' => 0),
            'totalCriteria' => AttributeType::Number,
            'finished'      => array(AttributeType::Bool, 'default' => false),
            'file'          => AttributeType::String,
            'map'           => AttributeType::Mixed,
            'criteria'      => AttributeType::Mixed,
            'submissions'   => AttributeType::Mixed
        );
    }

    /**
     * @return array
     */
    public function defineRelations()
    {
        return array(
            'form' => array(static::BELONGS_TO, 'AmForms_FormRecord', 'required' => true, 'onDelete' => static::CASCADE)
        );
    }

    public function scopes()
    {
        return array(
            'ordered' => array(
                'order' => 'id desc'
            )
        );
    }
}
