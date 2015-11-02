<?php
namespace Craft;

class AmForms_FormModel extends BaseElementModel
{
    protected $elementType = AmFormsModel::ElementTypeForm;

    private $_fields;

    /**
     * Use the form handle as the string representation.
     *
     * @return string
     */
    function __toString()
    {
        return Craft::t($this->name);
    }

    /**
     * @access protected
     * @return array
     */
    protected function defineAttributes()
    {
        // Craft email settings
        $settings = craft()->email->getSettings();
        $systemEmail = !empty($settings['emailAddress']) ? $settings['emailAddress'] : '';
        $systemName =  !empty($settings['senderName']) ? $settings['senderName'] : '';

        return array_merge(parent::defineAttributes(), array(
            'id'                       => AttributeType::Number,
            'fieldLayoutId'            => AttributeType::Number,
            'redirectEntryId'          => AttributeType::Number,
            'name'                     => AttributeType::String,
            'handle'                   => AttributeType::String,
            'titleFormat'              => array(AttributeType::String, 'default' => "{dateCreated|date('D, d M Y H:i:s')}"),
            'submitAction'             => AttributeType::String,
            'submitButton'             => AttributeType::String,
            'afterSubmitText'          => AttributeType::Mixed,
            'submissionEnabled'        => array(AttributeType::Bool, 'default' => true),
            'sendCopy'                 => array(AttributeType::Bool, 'default' => false),
            'sendCopyTo'               => AttributeType::String,
            'notificationEnabled'      => array(AttributeType::Bool, 'default' => true),
            'notificationFilesEnabled' => array(AttributeType::Bool, 'default' => false),
            'notificationRecipients'   => array(AttributeType::String, 'default' => $systemEmail),
            'notificationSubject'      => array(AttributeType::String, 'default' => Craft::t('{formName} form was submitted')),
            'notificationSenderName'   => array(AttributeType::String, 'default' => $systemName),
            'notificationSenderEmail'  => array(AttributeType::String, 'default' => $systemEmail),
            'notificationReplyToEmail' => array(AttributeType::String, 'default' => $systemEmail),
            'formTemplate'             => AttributeType::String,
            'tabTemplate'              => AttributeType::String,
            'fieldTemplate'            => AttributeType::String,
            'notificationTemplate'     => AttributeType::String
        ));
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return array(
            'fieldLayout' => new FieldLayoutBehavior($this->elementType)
        );
    }

    /**
     * Returns the field layout used by this element.
     *
     * @return FieldLayoutModel|null
     */
    public function getFieldLayout()
    {
        return $this->asa('fieldLayout')->getFieldLayout();
    }

    /**
     * Returns the element's CP edit URL.
     *
     * @return string|false
     */
    public function getCpEditUrl()
    {
        return UrlHelper::getCpUrl('amforms/forms/edit/' . $this->id);
    }

    /**
     * Return the element's fields.
     *
     * @return array
     */
    public function getFields()
    {
        if (! isset($this->_fields)) {
            $this->_fields = array();
            $layoutFields = $this->getFieldLayout()->getFields();
            foreach ($layoutFields as $layoutField) {
                $field = $layoutField->getField();
                $this->_fields[ $field->handle ] = $field;
            }
        }

        return $this->_fields;
    }

    /**
     * Return the form's redirect Entry.
     *
     * @return null|EntryModel
     */
    public function getRedirectEntry()
    {
        if ($this->redirectEntryId) {
            return craft()->entries->getEntryById($this->redirectEntryId);
        }
        return null;
    }

    /**
     * Return the form's redirect URL.
     *
     * @return null|string
     */
    public function getRedirectUrl()
    {
        $entry = $this->getRedirectEntry();
        if ($entry) {
            return $entry->url;
        }
        return null;
    }

    /**
     * Display a field.
     *
     * @param string $handle
     *
     * @return string
     */
    public function displayField($handle)
    {
        return craft()->amForms_forms->displayField($this, $handle);
    }

    /**
     * Display the form.
     *
     * With this we can display the Form FieldType on a front-end template.
     * @example {{ entry.fieldHandle.first().displayForm() }}
     *
     * @return string
     */
    public function displayForm()
    {
        return craft()->amForms_forms->displayForm($this);
    }
}
