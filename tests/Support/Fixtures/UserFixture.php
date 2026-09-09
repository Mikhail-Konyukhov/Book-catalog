<?php

declare(strict_types=1);

namespace app\tests\Support\Fixtures;

use app\models\User;
use yii\test\ActiveFixture;

final class UserFixture extends ActiveFixture
{
    public $modelClass = User::class;
    public $dataFile = '@app/tests/Support/data/user.php';
}
