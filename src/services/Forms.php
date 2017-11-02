<?php
/**
 * Form manager for Craft.
 *
 * @author    a&m impact
 * @copyright Copyright (c) 2017 a&m impact
 * @link      http://www.am-impact.nl
 */

namespace amimpact\formmanager\services;

use amimpact\formmanager\events\FormEvent;
use amimpact\formmanager\FormManager;
use amimpact\formmanager\models\Form;
use amimpact\formmanager\records\Form as FormRecord;
use Craft;
use craft\base\Component;
use yii\db\Exception;

class Forms extends Component
{
    /**
     * @event FormEvent The event that is triggered before a form is saved.
     */
    const EVENT_BEFORE_SAVE_FORM = 'beforeSaveForm';

    /**
     * @event FormEvent The event that is triggered after a form is saved.
     */
    const EVENT_AFTER_SAVE_FORM = 'afterSaveForm';

    /**
     * Saves a form.
     *
     * @param Form $form          The form to be saved.
     * @param bool $runValidation Whether the form should be validated.
     *
     * @return bool
     * @throws \yii\db\Exception
     * @throws \yii\base\InvalidParamException
     */
    public function saveForm(Form $form, bool $runValidation = true)
    {
        $isNewForm = ! $form->id;

        // Fire a 'beforeSaveForm' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_FORM)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_FORM, new FormEvent([
                'form' => $form,
                'isNew' => $isNewForm
            ]));
        }

        // Valid form?
        if ($runValidation && ! $form->validate()) {
            Craft::info('Form not saved due to validation error.', __METHOD__);
            return false;
        }

        // Existing form?
        if ($isNewForm) {
            $formRecord = FormRecord::findOne($form->id);
            if (! $formRecord) {
                throw new Exception("No form exists with the ID '{$form->id}'");
            }
        }
        else {
            $formRecord = new FormRecord();
        }

        // General settings
        $formRecord->setAttributes($form->getAttributes());

        $db = Craft::$app->getDb();
    }
}
