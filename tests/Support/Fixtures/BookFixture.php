<?php

declare(strict_types=1);

namespace app\tests\Support\Fixtures;

use app\models\Book;
use yii\test\ActiveFixture;

final class BookFixture extends ActiveFixture
{
    public $modelClass = Book::class;
    public $dataFile = '@app/tests/Support/data/book.php';
}
