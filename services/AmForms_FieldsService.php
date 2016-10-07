<?php
namespace Craft;

/**
 * AmForms - Fields service
 */
class AmForms_FieldsService extends BaseApplicationComponent
{
    /**
     * Get support fields.
     *
     * @param array $fieldTypes
     *
     * @return array
     */
    public function getProperFieldTypes($fieldTypes)
    {
        $basicFields = array();
        $advancedFields = array();
        $fieldTypeGroups = array();

        // Supported fields for displayForm functionality
        $supported = $this->getSupportedFieldTypes();

        // Set allowed fields
        foreach ($fieldTypes as $key => $fieldType) {
            if (in_array($key, $supported)) {
                $basicFields[$key] = $fieldType;
            }
            else {
                $advancedFields[$key] = $fieldType;
            }
        }

        $fieldTypeGroups['basic'] = array('optgroup' => Craft::t('Basic fields'));
        foreach ($basicFields as $key => $fieldType) {
            $fieldTypeGroups[$key] = $fieldType;
        }

        if(craft()->userSession->isAdmin()) {
            $fieldTypeGroups['advanced'] = array('optgroup' => Craft::t('Advanced fields'));
            foreach ($advancedFields as $key => $fieldType) {
                $fieldTypeGroups[$key] = $fieldType;
            }
        }

        return $fieldTypeGroups;
    }

    /**
     * Get supported field types.
     *
     * @return array
     */
    public function getSupportedFieldTypes()
    {
        return array(
            'AmForms_Hidden',
            'Assets',
            'Checkboxes',
            'Date',
            'Dropdown',
            'MultiSelect',
            'Number',
            'PlainText',
            'RadioButtons',
            'AmForms_Email',
        );
    }
}
