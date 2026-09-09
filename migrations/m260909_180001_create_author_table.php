<?php

declare(strict_types=1);

use yii\db\Migration;

class m260909_180001_create_author_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%author}}', [
            'id' => $this->primaryKey(),
            'last_name' => $this->string(100)->notNull(),
            'first_name' => $this->string(100)->notNull(),
            'middle_name' => $this->string(100)->null(),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB');

        // Поиск и сортировка авторов идут по фамилии.
        $this->createIndex('idx-author-last_name', '{{%author}}', 'last_name');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%author}}');
    }
}
