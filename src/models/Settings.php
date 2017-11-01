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
     * Antispam settings.
     */
    public $antispamHoneypotEnabled = true;
    public $antispamHoneypotName = 'yourssince1615';
    public $antispamTimeCheckEnabled = true;
    public $antispamMinimumTimeInSeconds = 3;
    public $antispamDuplicateCheckEnabled = true;
    public $antispamOriginCheckEnabled = true;

    /**
     * Google reCAPTCHA settings.
     */
    public $recaptchaEnabled = false;
    public $recaptchaSiteKey = '';
    public $recaptchaSecretKey = '';

    /**
     * Export settings.
     */
    public $exportDelimiter = ';';

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
            [
                [
                    'pluginName',
                    'antispamHoneypotName',
                    'recaptchaSiteKey',
                    'recaptchaSecretKey',
                    'exportDelimiter'
                ],
                'string'
            ],
            [
                [
                    'antispamHoneypotEnabled',
                    'antispamTimeCheckEnabled',
                    'antispamDuplicateCheckEnabled',
                    'antispamOriginCheckEnabled',
                    'recaptchaEnabled'
                ],
                'boolean'
            ],
            ['antispamMinimumTimeInSeconds', 'number', 'integerOnly' => true],
        ];
    }
}
