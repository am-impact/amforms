<?php
/**
 * Forms for Craft.
 *
 * @author    a&m impact
 * @copyright Copyright (c) 2017 a&m impact
 * @link      http://www.am-impact.nl
 */

namespace amimpact\forms;

use amimpact\forms\models\Settings;
use amimpact\forms\variables\FormsVariable;

use Craft;
use craft\base\Plugin;
use craft\events\DefineComponentsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;

use yii\base\Event;

class Forms extends Plugin
{
    public static $plugin;

    /**
     * @inheritdoc
     */
    public $schemaVersion = '3.0.0';

    /**
     * @inheritdoc
     */
    public $hasCpSettings = true;

    /**
     * Init Forms.
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // Adjust plugin name?
        if (! empty($this->getSettings()->pluginName)) {
            $this->name = $this->getSettings()->pluginName;
        }

        // Register stuff
        $this->_registerServices();
        $this->_registerRoutes();
        $this->_registerVariables();
    }

    /**
     * @inheritdoc
     */
    public function getCpNavItem()
    {
        $navItem = parent::getCpNavItem();
        $navItem['subnav'] = [
            'submissions' => [
                'label' => Craft::t('forms', 'Submissions'),
                'url' => UrlHelper::cpUrl('forms/submissions/'),
            ],
            'forms' => [
                'label' => Craft::t('forms', 'Forms'),
                'url' => UrlHelper::cpUrl('forms/forms/'),
            ],
            'fields' => [
                'label' => Craft::t('forms', 'Fields'),
                'url' => UrlHelper::cpUrl('forms/fields/'),
            ],
            'exports' => [
                'label' => Craft::t('forms', 'Exports'),
                'url' => UrlHelper::cpUrl('forms/exports/'),
            ],
        ];

        return $navItem;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsResponse()
    {
        return Craft::$app->controller->renderTemplate('forms/settings/index', [
            'plugin' => $this,
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * Register our plugin's services.
     *
     * @return void
     */
    private function _registerServices()
    {
        $this->setComponents([
            'general' => \amimpact\forms\services\General::class,
        ]);
    }

    /**
     * Register Craft routes.
     *
     * @return void
     */
    private function _registerRoutes()
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $rules = [
                'forms' => 'forms/forms/index',
                'forms/forms/new' => 'forms/forms/edit',
                'forms/forms/edit/<formId:\d+>' => 'forms/forms/edit',
            ];

            $event->rules = array_merge($event->rules, $rules);
        });
    }

    /**
     * Register Craft variables.
     *
     * @return void
     */
    private function _registerVariables()
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_DEFINE_COMPONENTS, function(DefineComponentsEvent $event) {
            $event->components['forms'] = FormsVariable::class;
        });
    }
}
