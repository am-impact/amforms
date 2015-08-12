<?php
namespace Craft;

class AmForms_SubmissionRecord extends BaseRecord
{
    /**
     * Return table name
     *
     * @return string
     */
    public function getTableName()
    {
        return 'amforms_submissions';
    }

    /**
     * Define attributes
     *
     * @return array
     */
    public function defineAttributes()
    {
        return array(
            'ipAddress'     => AttributeType::String,
            'userAgent'     => AttributeType::Mixed,
            'submittedFrom' => AttributeType::String
        );
    }

    /**
     * @return array
     */
    public function defineRelations()
    {
        return array(
            'element' => array(static::BELONGS_TO, 'ElementRecord', 'id', 'required' => true, 'onDelete' => static::CASCADE),
            'form'    => array(static::BELONGS_TO, 'AmForms_FormRecord', 'required' => true, 'onDelete' => static::CASCADE)
        );
    }
}
