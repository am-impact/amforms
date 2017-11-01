<?php
/**
 * Form manager for Craft.
 *
 * @author    a&m impact
 * @copyright Copyright (c) 2017 a&m impact
 * @link      http://www.am-impact.nl
 */

namespace amimpact\formmanager\controllers;

use amimpact\formmanager\FormManager;

use Craft;
use craft\web\Controller;
use craft\web\View;

use yii\web\Response;

class FieldsController extends Controller
{
    /**
     * Called when a user brings up a field for editing before being displayed.
     *
     * @param int|null            $fieldId The field's ID, if editing an existing field.
     * @param FieldInterface|null $field   The field being edited, if there were any validation errors
     *
     * @return Response
     */
    public function actionEdit(int $fieldId = null, FieldInterface $field = null): Response
    {
        $fieldsService = Craft::$app->getFields();

        // Existing field?
        if ($fieldId) {
            $field = $fieldsService->getFieldById($fieldId);
        }

        return $this->renderTemplate('form-manager/fields/_edit', [
            'fieldId' => $fieldId,
            'field' => $field,
        ]);
    }
}
