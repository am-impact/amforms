<?php
namespace Craft;

class AmForms_NoteRecord extends BaseRecord
{
    /**
     * Return table name
     *
     * @return string
     */
    public function getTableName()
    {
        return 'amforms_notes';
    }

    /**
     * Define attributes
     *
     * @return array
     */
    public function defineAttributes()
    {
        return array(
            'name' => array(AttributeType::String, 'required' => true, 'label' => Craft::t('Name')),
            'text' => array(AttributeType::Mixed, 'required' => true, 'label' => Craft::t('Note'))
        );
    }

    /**
     * @return array
     */
    public function defineRelations()
    {
        return array(
            'submission' => array(static::BELONGS_TO, 'AmForms_SubmissionRecord', 'required' => true, 'onDelete' => static::CASCADE)
        );
    }

    public function scopes()
    {
        return array(
            'ordered' => array('order' => 'id desc')
        );
    }
}
