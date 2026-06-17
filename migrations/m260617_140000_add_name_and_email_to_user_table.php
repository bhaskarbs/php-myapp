<?php

use yii\db\Migration;

/**
 * Handles adding columns `name` and `email` to table `{{%user}}`.
 */
class m260617_140000_add_name_and_email_to_user_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%user}}', 'name', $this->string(255)->notNull());
        $this->addColumn('{{%user}}', 'email', $this->string(255)->notNull());
        $this->createIndex('idx-user-email', '{{%user}}', 'email', true);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx-user-email', '{{%user}}');
        $this->dropColumn('{{%user}}', 'email');
        $this->dropColumn('{{%user}}', 'name');
    }
}
