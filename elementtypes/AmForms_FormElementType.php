<?php
namespace Craft;

class AmForms_FormElementType extends BaseElementType
{
    /**
     * Returns the element type name.
     *
     * @return string
     */
    public function getName()
    {
        return Craft::t('Form');
    }

    /**
     * Returns whether this element type has content.
     *
     * @return bool
     */
    public function hasContent()
    {
        return false;
    }

    /**
     * Returns whether this element type stores data on a per-locale basis.
     *
     * @return bool
     */
    public function isLocalized()
    {
        return false;
    }

    /**
     * Returns this element type's sources.
     *
     * @param string|null $context
     *
     * @return array|false
     */
    public function getSources($context = null)
    {
        $sources = array(
            '*' => array(
                'label' => Craft::t('All forms'),
            )
        );
        return $sources;
    }

    /**
     * Returns the attributes that can be shown/sorted by in table views.
     *
     * @param string|null $source
     * @return array
     */
    public function defineAvailableTableAttributes($source = null)
    {
        return array(
            'name' => Craft::t('Name'),
            'handle' => Craft::t('Handle'),
            'numberOfFields' => Craft::t('Number of fields'),
            'totalSubmissions' => Craft::t('Total submissions')
        );
    }

    /**
     * Returns the attributes that can be sorted by in table views.
     *
     * @return array
     */
    public function defineSortableAttributes()
    {
        return array(
            'name' => Craft::t('Name'),
            'handle' => Craft::t('Handle')
        );
    }

    /**
     * @inheritDoc IElementType::getTableAttributeHtml()
     *
     * @param BaseElementModel $element
     * @param string           $attribute
     *
     * @return string
     */
    public function getTableAttributeHtml(BaseElementModel $element, $attribute)
    {
        switch ($attribute) {
            case 'handle':
                return '<code>' . $element->handle . '</code>';
                break;

            case 'numberOfFields':
                $totalFields = craft()->db->createCommand()
                                ->select('COUNT(*)')
                                ->from('fieldlayoutfields')
                                ->where('layoutId=:layoutId', array(':layoutId' => $element->fieldLayoutId))
                                ->queryScalar();

                return $totalFields;
                break;

            case 'totalSubmissions':
                $totalSubmissions = craft()->db->createCommand()
                                ->select('COUNT(*)')
                                ->from('amforms_submissions')
                                ->where('formId=:formId', array(':formId' => $element->id))
                                ->queryScalar();

                return $totalSubmissions;
                break;

            default:
                return parent::getTableAttributeHtml($element, $attribute);
                break;
        }
    }

    /**
     * Defines any custom element criteria attributes for this element type.
     *
     * @return array
     */
    public function defineCriteriaAttributes()
    {
        return array(
            'fieldLayoutId' => AttributeType::Number,
            'name'          => AttributeType::String,
            'handle'        => AttributeType::String
        );
    }

    /**
     * Defines which model attributes should be searchable.
     *
     * @return array
     */
    public function defineSearchableAttributes()
    {
        return array(
            'name',
            'handle'
        );
    }

    /**
     * Modifies an element query targeting elements of this type.
     *
     * @param DbCommand $query
     * @param ElementCriteriaModel $criteria
     * @return mixed
     */
    public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
    {
        $query->addSelect('forms.id,
                           forms.fieldLayoutId,
                           forms.redirectEntryId,
                           forms.name,
                           forms.handle,
                           forms.titleFormat,
                           forms.submitAction,
                           forms.submitButton,
                           forms.afterSubmitText,
                           forms.submissionEnabled,
                           forms.sendCopy,
                           forms.sendCopyTo,
                           forms.notificationEnabled,
                           forms.notificationFilesEnabled,
                           forms.notificationRecipients,
                           forms.notificationSubject,
                           forms.notificationSenderName,
                           forms.notificationSenderEmail,
                           forms.notificationReplyToEmail,
                           forms.formTemplate,
                           forms.tabTemplate,
                           forms.fieldTemplate,
                           forms.notificationTemplate');
        $query->join('amforms_forms forms', 'forms.id = elements.id');

        if ($criteria->handle) {
            $query->andWhere(DbHelper::parseParam('forms.handle', $criteria->handle, $query->params));
        }
    }

    /**
     * Populates an element model based on a query result.
     *
     * @param array $row
     *
     * @return AmForms_FormModel
     */
    public function populateElementModel($row)
    {
        return AmForms_FormModel::populateModel($row);
    }
}
