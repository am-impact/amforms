<?php
namespace Craft;

/**
 * AmForms - Fields controller
 */
class AmForms_FieldsController extends BaseController
{
    /**
     * Show fields.
     */
    public function actionIndex()
    {
        $variables = array(
            'fields' => craft()->fields->getAllFields('id', AmFormsModel::FieldContext)
        );
        $this->renderTemplate('amForms/fields/index', $variables);
    }

    /**
     * Create or edit a field.
     *
     * @param array $variables
     */
    public function actionEditField(array $variables = array())
    {
        // Do we have a field model?
        if (! isset($variables['field'])) {
            // Get field if available
            if (! empty($variables['fieldId'])) {
                $variables['field'] = craft()->fields->getFieldById($variables['fieldId']);

                if (! $variables['field']) {
                    throw new Exception(Craft::t('No field exists with the ID “{id}”.', array('id' => $variables['fieldId'])));
                }
            }
            else {
                $variables['field'] = new FieldModel();
            }
        }

        // Get all field types
        $allFieldTypes = craft()->fields->getAllFieldTypes();

        // Supported field types
        $fieldTypes = array();
        $supported = array(
            'Checkboxes',
            'Dropdown',
            'MultiSelect',
            'Number',
            'PlainText',
            'RadioButtons'
        );
        foreach ($allFieldTypes as $key => $fieldType) {
            if (in_array($fieldType->getClassHandle(), $supported)) {
                $fieldTypes[$key] = $fieldType;
            }
        }
        $variables['fieldTypes'] = FieldTypeVariable::populateVariables($fieldTypes);

        $this->renderTemplate('amforms/fields/_edit', $variables);
    }

    /**
     * Save a field.
     */
    public function actionSaveField()
    {
        $this->requirePostRequest();

        // Get field if available
        $fieldId = craft()->request->getPost('fieldId');
        if ($fieldId) {
            $field = craft()->fields->getFieldById($fieldId);

            if (! $field) {
                throw new Exception(Craft::t('No field exists with the ID “{id}”.', array('id' => $fieldId)));
            }
        }
        else {
            $field = new FieldModel();
        }

        // Set attributes
        $field->name         = craft()->request->getPost('name');
        $field->handle       = craft()->request->getPost('handle');
        $field->instructions = craft()->request->getPost('instructions');
        $field->translatable = (bool) craft()->request->getPost('translatable');
        $field->type         = craft()->request->getRequiredPost('type');

        $typeSettings = craft()->request->getPost('types');
        if (isset($typeSettings[$field->type])) {
            $field->settings = $typeSettings[$field->type];
        }

        // Set field context
        craft()->content->fieldContext = AmForms_FormModel::getFieldContext();

        // Save field
        if (craft()->fields->saveField($field)) {
            craft()->userSession->setNotice(Craft::t('Field saved.'));

            $this->redirectToPostedUrl($field);
        }
        else {
            craft()->userSession->setError(Craft::t('Couldn’t save field.'));

            // Send the field back to the template
            craft()->urlManager->setRouteVariables(array(
                'field' => $field
            ));
        }
    }
}