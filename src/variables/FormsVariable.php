<?php
/**
 * Forms for Craft.
 *
 * @author    a&m impact
 * @copyright Copyright (c) 2017 a&m impact
 * @link      http://www.am-impact.nl
 */

namespace amimpact\forms\variables;

use amimpact\forms\Forms;

class FormsVariable
{
    public function getName()
    {
        return Forms::$plugin->name;
    }

    public function getSubNav()
    {
        return Forms::$plugin->getCpNavItem()['subnav'];
    }
}
