<?php
/**
 * Form manager for Craft.
 *
 * @author    a&m impact
 * @copyright Copyright (c) 2017 a&m impact
 * @link      http://www.am-impact.nl
 */

namespace amimpact\formmanager\variables;

use amimpact\formmanager\FormManager;
use amimpact\formmanager\variables\Forms;

use yii\di\ServiceLocator;

class General extends ServiceLocator
{
    public function init()
    {
        parent::init();

        $this->set('forms', Forms::class);
    }

    public function getName()
    {
        return FormManager::$plugin->name;
    }

    public function getSubNav()
    {
        return FormManager::$plugin->getCpNavItem()['subnav'];
    }
}
