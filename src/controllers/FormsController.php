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
use amimpact\formmanager\models\Form;
use craft\web\Controller;
use yii\web\Response;

class FormsController extends Controller
{
    /**
     * Called when a user brings up a form for editing before being displayed.
     *
     * @param int|null  $formId The form's ID, if editing an existing form.
     * @param Form|null $form   The form being edited, if there were any validation errors.
     *
     * @return Response
     * @throws \yii\base\InvalidParamException
     */
    public function actionEdit(int $formId = null, Form $form = null): Response
    {
        if ($formId !== null) {
            if ($form === null) {
                // Get form
            }
        }

        return $this->renderTemplate('form-manager/forms/_edit', []);
    }
}
