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
        return HtmlHelper::encodeParams('<input class="text fullwidth" type="email" name="{name}" value="{value}"/>', array('name' => $name, 'value' => $value));
    }
}
