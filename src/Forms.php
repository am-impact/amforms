<?php
/**
 * Forms for Craft.
 *
 * @author    a&m impact
 * @copyright Copyright (c) 2017 a&m impact
 * @link      http://www.am-impact.nl
 */

namespace amimpact\forms;

use Craft;
use craft\base\Plugin;

class Forms extends Plugin
{
    public static $plugin;

    /**
     * @inheritdoc
     */
    public $schemaVersion = '3.0.0';

    /**
     * Init Forms.
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->setComponents([
            'general' => \amimpact\forms\services\General::class,
        ]);
    }
}
