<?php
namespace Craft;

/**
 * Form fieldtype
 */
class AmForms_FormFieldType extends BaseElementFieldType
{
    protected $elementType = AmFormsModel::ElementTypeForm;
    protected $allowMultipleSources = false;

    /**
     * Returns the label for the "Add" button.
     *
     * @return string
     */
    protected function getAddButtonLabel()
    {
        return Craft::t('Add a form');
    }
}
