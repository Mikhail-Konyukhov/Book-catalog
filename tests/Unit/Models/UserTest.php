<?php

declare(strict_types=1);

namespace app\tests\Unit\Models;

use app\models\User;
use app\tests\Support\Fixtures\UserFixture;

final class UserTest extends \Codeception\Test\Unit
{
    public function _fixtures(): array
    {
        return ['user' => UserFixture::class];
    }

    public function testFindUserById(): void
    {
        $user = User::findIdentity(100);

        verify($user)->notEmpty();
        verify($user->username)->equals('admin');
        verify(User::findIdentity(999))->empty();
    }

    public function testFindUserByUsername(): void
    {
        verify(User::findByUsername('admin'))->notEmpty();
        verify(User::findByUsername('not-admin'))->empty();
    }

    public function testValidateAuthKey(): void
    {
        $user = User::findByUsername('admin');

        verify($user->validateAuthKey('test100key'))->true();
        verify($user->validateAuthKey('test101key'))->false();
    }

    public function testValidatePassword(): void
    {
        $user = User::findByUsername('admin');

        verify($user->validatePassword('admin'))->true();
        verify($user->validatePassword('wrong'))->false();
    }
}
