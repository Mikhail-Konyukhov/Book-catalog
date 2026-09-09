<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * @property int $id
 * @property string $username
 * @property string $password_hash
 * @property string $auth_key
 */
class User extends ActiveRecord implements IdentityInterface
{
    public static function tableName(): string
    {
        return '{{%user}}';
    }

    public function rules(): array
    {
        return [
            [['username', 'password_hash', 'auth_key'], 'required'],
            ['username', 'string', 'max' => 64],
            ['username', 'unique'],
            ['password_hash', 'string', 'max' => 255],
            ['auth_key', 'string', 'max' => 32],
        ];
    }

    public static function findIdentity($id): static|null
    {
        return static::findOne(['id' => $id]);
    }

    /**
     * Приложение web, API-токенов нет.
     */
    public static function findIdentityByAccessToken($token, $type = null): static|null
    {
        return null;
    }

    public static function findByUsername(string $username): static|null
    {
        return static::findOne(['username' => $username]);
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function getAuthKey(): string|null
    {
        return $this->auth_key;
    }

    public function validateAuthKey($authKey): bool
    {
        return $this->auth_key === $authKey;
    }

    public function validatePassword(string $password): bool
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }
}
