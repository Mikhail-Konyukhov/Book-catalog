<?php

declare(strict_types=1);

use yii\db\Migration;

class m260909_180002_create_book_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%book}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'year' => 'SMALLINT UNSIGNED NOT NULL',
            'description' => $this->text()->null(),
            'isbn' => $this->string(20)->notNull(),
            'cover_path' => $this->string(255)->null(),
            'created_at' => $this->integer()->notNull(),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB');

        $this->createIndex('idx-book-isbn', '{{%book}}', 'isbn', true);
        // Под отчёт ТОП-10 авторов за год.
        $this->createIndex('idx-book-year', '{{%book}}', 'year');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%book}}');
    }
}
