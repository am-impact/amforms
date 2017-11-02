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
use yii\di\ServiceLocator;

/**
 * Class General
 *
 * @package amimpact\formmanager\variables
 * @property null|string $name
 * @property mixed       $subNav
 */
class General extends ServiceLocator
{
    public function init()
    {
        parent::init();

        $this->set('forms', Forms::class);
    }

    /**
     * @return null|string
     */
    public function getName()
    {
        return FormManager::$plugin->name;
    }

    /**
     * @return mixed
     */
    public function getSubNav()
    {
        return FormManager::$plugin->getCpNavItem()['subnav'];
    }
}
