<?php
namespace Craft;

class m150723_112500_amForms_redirectUriToRelation extends BaseMigration
{
    public function safeUp()
    {
        // Get all current forms
        $forms = craft()->db->createCommand()
                ->select('*')
                ->from('amforms_forms')
                ->queryAll();

        // Find entries if the redirectUri is set
        $entries = array();
        if ($forms) {
            foreach ($forms as $form) {
                if ($form['redirectUri'] && trim($form['redirectUri']) != '') {
                    $entry = $this->_findEntryByUri($form['redirectUri']);
                    if ($entry) {
                        $entries[ $form['id'] ] = $entry->id;
                    }
                }
            }
        }

        // Alter redirectUri to redirectEntryId
        // We drop it first, because it seems altering and adding a key won't work
        $this->dropColumn('amforms_forms', 'redirectUri');
        $this->addColumnAfter('amforms_forms', 'redirectEntryId', array(ColumnType::Int), 'fieldLayoutId');
        $this->addForeignKey('amforms_forms', 'redirectEntryId', 'entries', 'id', 'SET NULL', null);

        foreach ($entries as $formId => $entryId) {
            craft()->db->createCommand()->update('amforms_forms', array(
                'redirectEntryId' => $entryId
            ), array(
                'id' => $formId
            ));
        }
    }

    /**
     * Find an Entry by its URI.
     *
     * @param string $uri
     *
     * @return mixed
     */
    private function _findEntryByUri($uri)
    {
        $criteria = craft()->elements->getCriteria(ElementType::Entry);
        $criteria->locale = craft()->i18n->getPrimarySiteLocaleId();
        $criteria->uri = str_replace('{siteUrl}', '', $uri);
        $criteria->limit = 1;
        if ($criteria->total() > 0) {
            return $criteria->first();
        }
        return false;
    }
}
