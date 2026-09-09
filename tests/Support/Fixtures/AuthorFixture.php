<?php

declare(strict_types=1);

namespace app\tests\Support\Fixtures;

use app\models\Author;
use yii\test\ActiveFixture;

final class AuthorFixture extends ActiveFixture
{
    public $modelClass = Author::class;
    public $dataFile = '@app/tests/Support/data/author.php';
}
