<?php

namespace Craft;

/**
 * Email fieldtype.
 */
class AmForms_EmailFieldType extends BaseFieldType
{
    /**
     * @inheritDoc IComponentType::getName()
     *
     * @return string
     */
    public function getName()
    {
        return Craft::t('E-mail');
    }

    /**
     * @inheritDoc IFieldType::defineContentAttribute()
     *
     * @return mixed
     */
    public function defineContentAttribute()
    {
        return AttributeType::Email;
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
        return craft()->templates->render('_components/fieldtypes/PlainText/input', array(
            'name'     => $name,
            'value'    => $value,
            'settings' => $this->getSettings(),
            'type'     => 'email',
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
            'placeholder'   => array(AttributeType::String),
            'multiline'     => array(AttributeType::Bool),
            'initialRows'   => array(AttributeType::Number, 'min' => 1, 'default' => 4),
            'maxLength'     => array(AttributeType::Number, 'min' => 0),
        );
    }
}
