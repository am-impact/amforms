<?php

namespace Craft;

/**
 * Hidden fieldtype.
 */
class AmForms_HiddenFieldType extends BaseFieldType
{
    /**
     * @inheritDoc IComponentType::getName()
     *
     * @return string
     */
    public function getName()
    {
        return Craft::t('Hidden');
    }

    /**
    * @inheritDoc ISavableComponentType::getSettingsHtml()
    *
    * @return string|null
    */
    public function getSettingsHtml()
    {
        return craft()->templates->render('amforms/_display/templates/_components/fieldtypes/Hidden/settings', array(
            'settings' => $this->getSettings()
        ));
    }

    /**
     * @inheritDoc IFieldType::defineContentAttribute()
     *
     * @return mixed
     */
    public function defineContentAttribute()
    {
        return AttributeType::String;
    }

    /**
     * @inheritDoc IFieldType::getInputHtml()
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return string
     */
    public function getInputHtml($name, $value)
    {
        // Get field settings
        $settings = $this->getSettings();

        // Is this a CP request?
        $isCpRequest = craft()->request->isCpRequest();

        // Where is the template located?
        $templatePath = ($isCpRequest ? 'amforms/_display/templates/' : '') . '_components/fieldtypes/Hidden/input';

        return craft()->templates->render($templatePath, array(
            'name'        => $name,
            'value'       => craft()->templates->renderString($settings->value),
            'isCpRequest' => $isCpRequest,
        ));
    }

    /**
     * @inheritDoc BaseSavableComponentType::defineSettings()
     *
     * @return array
     */
    protected function defineSettings()
    {
        return array(
            'value' => array(AttributeType::String),
        );
    }
}
