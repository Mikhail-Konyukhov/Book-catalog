<?php

declare(strict_types=1);

use yii\db\Migration;

class m260909_180003_create_book_author_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%book_author}}', [
            'book_id' => $this->integer()->notNull(),
            'author_id' => $this->integer()->notNull(),
            'PRIMARY KEY(book_id, author_id)',
        ], 'ENGINE=InnoDB');

        $this->createIndex('idx-book_author-author_id', '{{%book_author}}', 'author_id');

        $this->addForeignKey('fk-book_author-book_id', '{{%book_author}}', 'book_id', '{{%book}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-book_author-author_id', '{{%book_author}}', 'author_id', '{{%author}}', 'id', 'CASCADE');
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk-book_author-author_id', '{{%book_author}}');
        $this->dropForeignKey('fk-book_author-book_id', '{{%book_author}}');
        $this->dropTable('{{%book_author}}');
    }
}
