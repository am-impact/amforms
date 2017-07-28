<?php
/**
 * Form manager for Craft.
 *
 * @author    a&m impact
 * @copyright Copyright (c) 2017 a&m impact
 * @link      http://www.am-impact.nl
 */

namespace amimpact\formmanager\models;

use amimpact\formmanager\FormManager;

use Craft;
use craft\base\Model;

class Settings extends Model
{
    /**
     * General settings.
     */
    public $pluginName = '';

    /**
     * Returns the validation rules for attributes.
     *
     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     *
     * @return array
     */
    public function rules()
    {
        return [
            ['pluginName', 'string'],
        ];
    }
}
