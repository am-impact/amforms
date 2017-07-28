<?php
/**
 * Form manager for Craft.
 *
 * @author    a&m impact
 * @copyright Copyright (c) 2017 a&m impact
 * @link      http://www.am-impact.nl
 */

namespace amimpact\formmanager\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\MigrationHelper;

/**
 * Install migration.
 */
class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTables();
        $this->createIndexes();
        $this->addForeignKeys();
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropTable('{{%formmanager_submissions}}');
        $this->dropTable('{{%formmanager_forms}}');
    }

    /**
     * Create necessary tables.
     *
     * @return void
     */
    protected function createTables()
    {
        $this->createTable('{{%formmanager_forms}}', [
            'id' => $this->integer()->notNull(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'authorId' => $this->integer(),
            'fieldLayoutId' => $this->integer(),
            'titleFormat' => $this->string()->notNull(),
            'submitActionUrl' => $this->string(),
            'submitButtonText' => $this->string(),
            'afterSubmit' => $this->string(),
            'afterSubmitText' => $this->text(),
            'redirectEntryId' => $this->integer(),
            'notificationIds' => $this->string(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
            'PRIMARY KEY(id)',
        ]);
        $this->createTable('{{%formmanager_submissions}}', [
            'id' => $this->integer()->notNull(),
            'formId' => $this->integer()->notNull(),
            'origin' => $this->string(),
            'ipAddress' => $this->string(),
            'userAgent' => $this->string(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
            'PRIMARY KEY(id)',
        ]);
    }

    /**
     * Create necessary indexes.
     *
     * @return void
     */
    protected function createIndexes()
    {
        $this->createIndex(null, '{{%formmanager_forms}}', 'name', true);
        $this->createIndex(null, '{{%formmanager_forms}}', 'handle', true);
        $this->createIndex(null, '{{%formmanager_forms}}', 'authorId', false);
        $this->createIndex(null, '{{%formmanager_forms}}', 'fieldLayoutId', false);
    }

    /**
     * Create necessary foreign keys.
     *
     * @return void
     */
    protected function addForeignKeys()
    {
        $this->addForeignKey(null, '{{%formmanager_forms}}', 'authorId', '{{%users}}', 'id', 'SET NULL', null);
        $this->addForeignKey(null, '{{%formmanager_forms}}', 'fieldLayoutId', '{{%fieldlayouts}}', 'id', 'SET NULL', null);
        $this->addForeignKey(null, '{{%formmanager_forms}}', 'redirectEntryId', '{{%entries}}', 'id', 'SET NULL', null);
        $this->addForeignKey(null, '{{%formmanager_submissions}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%formmanager_submissions}}', 'formId', '{{%formmanager_forms}}', 'id', 'CASCADE', null);
    }
}
