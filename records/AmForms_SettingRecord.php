<?php
namespace Craft;

class AmForms_SettingRecord extends BaseRecord
{
    public function getTableName()
    {
        return 'amforms_settings';
    }

    protected function defineAttributes()
    {
        return array(
            'enabled' => array(AttributeType::Bool, 'required' => true, 'default' => false),
            'type'    => array(AttributeType::String, 'required' => true),
            'name'    => array(AttributeType::String, 'required' => true),
            'handle'  => array(AttributeType::String, 'required' => true),
            'value'   => array(AttributeType::Mixed)
        );
    }

    public function defineIndexes()
    {
        return array(
            array('columns' => array('type'), 'unique' => false),
            array('columns' => array('type', 'handle'), 'unique' => true)
        );
    }

    /**
     * @return array
     */
    public function scopes()
    {
        return array(
            'ordered' => array('order' => 'handle')
        );
    }
}
