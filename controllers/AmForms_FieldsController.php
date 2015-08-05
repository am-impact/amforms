<?php
namespace Craft;

/**
 * AmForms - Fields controller
 */
class AmForms_FieldsController extends BaseController
{
    /**
     * Make sure the current has access.
     */
    public function __construct()
    {
        $user = craft()->userSession->getUser();
        if (! $user->can('accessAmFormsFields')) {
            throw new HttpException(403, Craft::t('This action may only be performed by users with the proper permissions.'));
        }
    }

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
        craft()->content->fieldContext = AmFormsModel::FieldContext;
        craft()->content->contentTable = AmFormsModel::FieldContent;

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

    /**
     * Delete a field.
     */
    public function actionDeleteField()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();

        // Override Craft's default context and content
        craft()->content->fieldContext = AmFormsModel::FieldContext;
        craft()->content->contentTable = AmFormsModel::FieldContent;

        // Delete field
        $fieldId = craft()->request->getRequiredPost('id');
        $success = craft()->fields->deleteFieldById($fieldId);

        $this->returnJson(array('success' => $success));
    }
}
