<?php

declare(strict_types=1);

use yii\db\Migration;

class m260909_180005_create_user_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%user}}', [
            'id' => $this->primaryKey(),
            'username' => $this->string(64)->notNull(),
            'password_hash' => $this->string(255)->notNull(),
            // Без auth_key ломается «запомнить меня», включённое в шаблоне.
            'auth_key' => $this->string(32)->notNull(),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB');

        $this->createIndex('idx-user-username', '{{%user}}', 'username', true);

        $security = Yii::$app->security;
        $this->insert('{{%user}}', [
            'username' => 'admin',
            'password_hash' => $security->generatePasswordHash('admin123'),
            'auth_key' => $security->generateRandomString(),
        ]);
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%user}}');
    }
}
