<?php
namespace Craft;

class AmForms_SubmissionElementType extends BaseElementType
{
    /**
     * Returns the element type name.
     *
     * @return string
     */
    public function getName()
    {
        return Craft::t('Submission');
    }

    /**
     * Returns whether this element type has content.
     *
     * @return bool
     */
    public function hasContent()
    {
        return true;
    }

    /**
     * Returns whether this element type has titles.
     *
     * @return bool
     */
    public function hasTitles()
    {
        return true;
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
                'label'       => Craft::t('All submissions'),
                'defaultSort' => array('dateCreated', 'desc')
            )
        );

        $forms = craft()->amForms_forms->getAllForms();
        if ($forms) {
            foreach ($forms as $form) {
                $key = 'formId:'.$form->id;
                $sources[$key] = array(
                    'label'       => $form->name,
                    'criteria'    => array('formId' => $form->id),
                    'defaultSort' => array('dateCreated', 'desc')
                );
            }
        }

        return $sources;
    }

    /**
     * Returns the content table name that should be joined in for an elements query.
     *
     * @param ElementCriteriaModel
     *
     * @throws Exception
     * @return string
     */
    public function getContentTableForElementsQuery(ElementCriteriaModel $criteria)
    {
        return AmFormsModel::FieldContent;
    }

    /**
     * Returns the fields that should be available for the elements query.
     *
     * @param ElementCriteriaModel $criteria
     *
     * @return FieldModel[]
     */
    public function getFieldsForElementsQuery(ElementCriteriaModel $criteria)
    {
        return craft()->fields->getAllFields(null, AmFormsModel::FieldContext);
    }

    /**
     * Returns this element type's actions.
     *
     * @param string|null $source
     *
     * @return array|null
     */
    public function getAvailableActions($source = null)
    {
        // Get export action
        $exportAction = craft()->elements->getAction('AmForms_Export');

        // Get delete action
        $deleteAction = craft()->elements->getAction('Delete');
        $deleteAction->setParams(array(
            'confirmationMessage' => Craft::t('Are you sure you want to delete the selected submissions?'),
            'successMessage'      => Craft::t('Submissions deleted.'),
        ));

        // Set actions
        return array($exportAction, $deleteAction);
    }

    /**
     * Returns the attributes that can be shown/sorted by in table views.
     *
     * @param string|null $source
     *
     * @return array
     */
    public function defineAvailableTableAttributes($source = null)
    {
        // Don't display the form's name, since the source already indicates that!
        if ($source && $source !== '*') {
            return array(
                'title'       => Craft::t('Title'),
                'dateCreated' => Craft::t('Date created'),
                'dateUpdated' => Craft::t('Date updated'),
                'notes'       => Craft::t('Notes')
            );
        }

        return array(
            'title'       => Craft::t('Title'),
            'formName'    => Craft::t('Form name'),
            'dateCreated' => Craft::t('Date created'),
            'dateUpdated' => Craft::t('Date updated'),
            'notes'       => Craft::t('Notes')
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
            case 'notes':
                $notes = craft()->db->createCommand()
                        ->select('COUNT(*)')
                        ->from('amforms_notes')
                        ->where('submissionId=:submissionId', array(':submissionId' => $element->id))
                        ->queryScalar();

                return sprintf('<a href="%s">%d</a>',
                    $element->getCpEditUrl() . '/notes',
                    $notes
                );
                break;

            default:
                return parent::getTableAttributeHtml($element, $attribute);
                break;
        }
    }

    /**
     * Returns the attributes that can be sorted by in table views.
     *
     * @return array
     */
    public function defineSortableAttributes()
    {
        return array(
            'formName'    => Craft::t('Form name'),
            'dateCreated' => Craft::t('Date created'),
            'dateUpdated' => Craft::t('Date updated')
        );
    }

    /**
     * Defines any custom element criteria attributes for this element type.
     *
     * @return array
     */
    public function defineCriteriaAttributes()
    {
        return array(
            'order'      => array(AttributeType::String, 'default' => 'dateCreated desc'),
            'title'      => AttributeType::String,
            'formId'     => AttributeType::Number,
            'formHandle' => AttributeType::String
        );
    }

    /**
     * Defines which model attributes should be searchable.
     *
     * @return array
     */
    public function defineSearchableAttributes()
    {
        return array('id', 'title', 'formName');
    }

    /**
     * Modifies an element query targeting elements of this type.
     *
     * @param DbCommand            $query
     * @param ElementCriteriaModel $criteria
     *
     * @return mixed
     */
    public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
    {
        $query->addSelect('submissions.id,
                           submissions.ipAddress,
                           submissions.userAgent,
                           submissions.submittedFrom,
                           submissions.dateCreated,
                           submissions.dateUpdated,
                           submissions.uid,
                           forms.id as formId,
                           forms.name as formName');
        $query->join('amforms_submissions submissions', 'submissions.id = elements.id');
        $query->join('amforms_forms forms', 'forms.id = submissions.formId');

        if ($criteria->id) {
            $query->andWhere(DbHelper::parseParam('submissions.id', $criteria->id, $query->params));
        }
        if ($criteria->formId) {
            $query->andWhere(DbHelper::parseParam('submissions.formId', $criteria->formId, $query->params));
        }
        if ($criteria->formHandle) {
            $query->andWhere(DbHelper::parseParam('forms.handle', $criteria->formHandle, $query->params));
        }
        if ($criteria->order) {
            // Trying to order by date creates ambiguity errors
            // Let's make sure mysql knows what we want to sort by
            if (stripos($criteria->order, 'elements.') === false && stripos($criteria->order, 'submissions.dateCreated') === false) {
                $criteria->order = str_replace('dateCreated', 'submissions.dateCreated', $criteria->order);
                $criteria->order = str_replace('dateUpdated', 'submissions.dateUpdated', $criteria->order);
            }

            // If we are sorting by title and do not have a source
            // We won't be able to sort, so bail on it
            if (stripos($criteria->order, 'title') !== false && ! $criteria->formId) {
                $criteria->order = null;
            }
        }
    }

    /**
     * Populates an element model based on a query result.
     *
     * @param array $row
     *
     * @return array
     */
    public function populateElementModel($row)
    {
        return AmForms_SubmissionModel::populateModel($row);
    }
}
