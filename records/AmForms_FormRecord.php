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
            'name'                     => array(AttributeType::String, 'required' => true),
            'handle'                   => array(AttributeType::String, 'required' => true),
            'titleFormat'              => array(AttributeType::String, 'required' => true),
            'submitAction'             => AttributeType::String,
            'submitButton'             => AttributeType::String,
            'afterSubmitText'          => AttributeType::Mixed,
            'submissionEnabled'        => array(AttributeType::Bool, 'default' => true),
            'sendCopy'                 => array(AttributeType::Bool, 'default' => false),
            'sendCopyTo'               => AttributeType::String,
            'notificationEnabled'      => array(AttributeType::Bool, 'default' => true),
            'notificationFilesEnabled' => array(AttributeType::Bool, 'default' => false),
            'notificationRecipients'   => AttributeType::String,
            'notificationSubject'      => AttributeType::String,
            'notificationSenderName'   => AttributeType::String,
            'notificationSenderEmail'  => AttributeType::String,
            'notificationReplyToEmail' => AttributeType::String,
            'formTemplate'             => AttributeType::String,
            'tabTemplate'              => AttributeType::String,
            'fieldTemplate'            => AttributeType::String,
            'notificationTemplate'     => AttributeType::String
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
            'element'       => array(static::BELONGS_TO, 'ElementRecord', 'id', 'required' => true, 'onDelete' => static::CASCADE),
            'fieldLayout'   => array(static::BELONGS_TO, 'FieldLayoutRecord', 'onDelete' => static::SET_NULL),
            'redirectEntry' => array(static::BELONGS_TO, 'EntryRecord', 'onDelete' => static::SET_NULL),
            'submissions'   => array(static::HAS_MANY, 'AmForms_SubmissionRecord', 'submissionId')
        );
    }
}
