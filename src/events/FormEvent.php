<?php
/**
 * Form manager for Craft.
 *
 * @author    a&m impact
 * @copyright Copyright (c) 2017 a&m impact
 * @link      http://www.am-impact.nl
 */

namespace amimpact\formmanager\events;

use yii\base\Event;

class FormEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var \amimpact\formmanager\models\Form|null The form model associated with the event.
     */
    public $form;

    /**
     * @var bool Whether the form is brand new.
     */
    public $isNew = false;
}
