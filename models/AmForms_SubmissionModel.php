<?php
namespace Craft;

class AmForms_SubmissionModel extends BaseElementModel
{
    protected $elementType = AmFormsModel::ElementTypeSubmission;

    /**
     * @return array
     */
    protected function defineAttributes()
    {
        return array_merge(parent::defineAttributes(),
            array(
                'id'        => AttributeType::Number,
                'form'      => AttributeType::Mixed,
                'formId'    => AttributeType::Number,
                'formName'  => AttributeType::String,
                'ipAddress' => AttributeType::String,
                'userAgent' => AttributeType::Mixed
            )
        );
    }

    /**
     * Returns the field layout used by this element
     *
     * @return FieldLayoutModel|null
     */
    public function getFieldLayout()
    {
        return $this->getForm()->getFieldLayout();
    }

    /**
     * Returns the fields associated with this form.
     *
     * @return array
     */
    public function getFields()
    {
        return $this->getForm()->getFields();
    }

    /**
     * Returns the content title.
     *
     * @return mixed|string
     */
    public function getTitle()
    {
        return $this->getContent()->title;
    }

    /**
     * Get the form model.
     *
     * @return AmForms_FormModel
     */
    public function getForm()
    {
        if (! isset($this->form)) {
            $this->form = craft()->amForms_forms->getFormById($this->formId);
        }

        return $this->form;
    }

    /**
     * Returns the element's CP edit URL.
     *
     * @return string
     */
    public function getCpEditUrl()
    {
        return UrlHelper::getCpUrl('amforms/submissions/edit/' . $this->id);
    }
}