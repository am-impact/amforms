<?php
namespace Craft;

class AmForms_FormRecord extends BaseRecord
{
    /**
     * Return table name
     *
     * @return string
     */
    public function getTableName()
    {
        return 'amforms_forms';
    }

    /**
     * Define attributes
     *
     * @return array
     */
    public function defineAttributes()
    {
        return array(
            'name'         => array(AttributeType::String, 'required' => true),
            'handle'       => array(AttributeType::String, 'required' => true),
            'titleFormat'  => array(AttributeType::String, 'required' => true),
            'redirectUri'  => AttributeType::String,
            'submitAction' => AttributeType::String,
            'submitButton' => AttributeType::String
        );
    }

    /**
     * Define validation rules
     *
     * @return array
     */
    public function rules()
    {
        return array(
            array(
                'name,handle',
                'required'
            ),
            array(
                'name,handle',
                'unique',
                'on' => 'insert'
            )
        );
    }

    /**
     * Define relationships
     *
     * @return array
     */
    public function defineRelations()
    {
        return array(
            'element'     => array(static::BELONGS_TO, 'ElementRecord', 'id', 'required' => true, 'onDelete' => static::CASCADE),
            'fieldLayout' => array(static::BELONGS_TO, 'FieldLayoutRecord', 'onDelete' => static::SET_NULL)
        );
    }
}