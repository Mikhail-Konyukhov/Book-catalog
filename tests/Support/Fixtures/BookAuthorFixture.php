<?php

declare(strict_types=1);

namespace app\tests\Support\Fixtures;

use yii\test\ActiveFixture;

/**
 * Junction table has no model of its own, so the fixture is bound to the table name.
 */
final class BookAuthorFixture extends ActiveFixture
{
    public $tableName = 'book_author';
    public $dataFile = '@app/tests/Support/data/book_author.php';
}
