<?php

declare(strict_types=1);

use yii\db\Migration;

class m260909_180004_create_subscription_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%subscription}}', [
            'id' => $this->primaryKey(),
            'author_id' => $this->integer()->notNull(),
            'phone' => $this->string(20)->notNull(),
            'created_at' => $this->integer()->notNull(),
            'notified_at' => $this->integer()->null(),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB');

        // Повторная подписка отсекается на уровне БД, а не только валидатором.
        $this->createIndex('idx-subscription-author_id-phone', '{{%subscription}}', ['author_id', 'phone'], true);
        $this->addForeignKey('fk-subscription-author_id', '{{%subscription}}', 'author_id', '{{%author}}', 'id', 'CASCADE');
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk-subscription-author_id', '{{%subscription}}');
        $this->dropTable('{{%subscription}}');
    }
}
