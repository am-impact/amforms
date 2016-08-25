<?php
namespace Craft;

/**
 * Submissions fieldtype
 */
class AmForms_SubmissionsFieldType extends BaseFieldType
{
    /**
     * @inheritDoc IComponentType::getName()
     *
     * @return string
     */
    public function getName()
    {
        return Craft::t('Form submissions');
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
        // Get criteria
        $criteria = craft()->amForms_submissions->getCriteria();
        $criteria->relatedTo = array(
            $this->element->id
        );

        return craft()->templates->render('amforms/_fields/submissions/input', array(
            'submissions' => $criteria->find()
        ));
    }
}
