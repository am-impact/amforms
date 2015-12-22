<?php
namespace Craft;

class AmForms_RecentSubmissionsWidget extends BaseWidget
{
    public function getName()
    {
        $name = Craft::t('Recent submissions');

        // Add form name, if a form was chosen
        if ($this->getSettings()->form != 0) {
            $form = craft()->amForms_forms->getFormById($this->getSettings()->form);

            if ($form) {
                $name .= ': ' . $form->name;
            }
        }

        return $name;
    }

    public function getBodyHtml()
    {
        // Widget settings
        $settings = $this->getSettings();

        // Set submissions criteria
        $criteria = craft()->amForms_submissions->getCriteria();
        if ($settings->form != 0) {
            $criteria->formId = $settings->form;
        }
        $criteria->limit = $settings->limit;

        return craft()->templates->render('amforms/_widgets/recentsubmissions/body', array(
            'submissions' => $criteria->find(),
            'settings'    => $settings
        ));
    }

    public function getSettingsHtml()
    {
        $forms = array(
            0 => Craft::t('All forms')
        );
        $availableForms = craft()->amForms_forms->getAllForms();
        if ($availableForms) {
            foreach ($availableForms as $form) {
                $forms[ $form->id ] = $form->name;
            }
        }

        return craft()->templates->render('amforms/_widgets/recentsubmissions/settings', array(
           'settings'       => $this->getSettings(),
           'availableForms' => $forms
        ));
    }

    protected function defineSettings()
    {
        return array(
           'form'     => array(AttributeType::Number, 'required' => true),
           'limit'    => array(AttributeType::Number, 'min' => 0, 'default' => 10),
           'showDate' => array(AttributeType::String)
        );
    }
}
