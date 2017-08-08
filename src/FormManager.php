<?php
/**
 * Form manager for Craft.
 *
 * @author    a&m impact
 * @copyright Copyright (c) 2017 a&m impact
 * @link      http://www.am-impact.nl
 */

namespace amimpact\formmanager;

use amimpact\formmanager\models\Settings;
use amimpact\formmanager\variables\Forms;

use Craft;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;

use yii\base\Event;

class FormManager extends Plugin
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
     * @inheritdoc
     */
    public $hasCpSection = true;

    /**
     * Init Form manager.
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
                'label' => Craft::t('form-manager', 'Submissions'),
                'url' => UrlHelper::cpUrl('form-manager/submissions/'),
            ],
            'forms' => [
                'label' => Craft::t('form-manager', 'Forms'),
                'url' => UrlHelper::cpUrl('form-manager/forms/'),
            ],
            'fields' => [
                'label' => Craft::t('form-manager', 'Fields'),
                'url' => UrlHelper::cpUrl('form-manager/fields/'),
            ],
            'exports' => [
                'label' => Craft::t('form-manager', 'Exports'),
                'url' => UrlHelper::cpUrl('form-manager/exports/'),
            ],
        ];

        return $navItem;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsResponse()
    {
        return Craft::$app->controller->renderTemplate('form-manager/settings/index', [
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
            'general' => \amimpact\formmanager\services\General::class,
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
                'form-manager' => '',
                'form-manager/forms/new' => 'form-manager/forms/edit',
                'form-manager/forms/edit/<formId:\d+>' => 'form-manager/forms/edit',
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
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            $variable = $event->sender;
            $variable->set('formmanager', Forms::class);
        });
    }
}
