<?php

declare(strict_types=1);

namespace app\tests\Unit\Models;

use app\models\Book;
use app\tests\Support\Fixtures\AuthorFixture;
use app\tests\Support\Fixtures\BookAuthorFixture;
use app\tests\Support\Fixtures\BookFixture;
use Yii;

final class BookTest extends \Codeception\Test\Unit
{
    public function _fixtures(): array
    {
        return [
            'author' => AuthorFixture::class,
            'book' => BookFixture::class,
            'bookAuthor' => BookAuthorFixture::class,
        ];
    }

    public function testYearAboveTheUpperBoundIsInvalid(): void
    {
        // Must fail on the form, not in MySQL: 99999 does not fit into SMALLINT UNSIGNED.
        $model = new Book([
            'title' => 'Из будущего',
            'year' => 99999,
            'isbn' => '978-5-0002-0001-0',
            'authorIds' => [1],
        ]);

        verify($model->validate())->false();
        verify($model->errors)->arrayHasKey('year');
    }

    public function testUnknownAuthorIdIsInvalid(): void
    {
        // Without this rule a forged POST reaches link() and breaks on the foreign key.
        $model = new Book([
            'title' => 'Ничей автор',
            'year' => 2021,
            'isbn' => '978-5-0002-0002-7',
            'authorIds' => [999],
        ]);

        verify($model->validate())->false();
        verify($model->errors)->arrayHasKey('authorIds');
    }

    public function testExistingIsbnIsInvalid(): void
    {
        $model = new Book([
            'title' => 'Двойник',
            'year' => 2021,
            'isbn' => '978-5-0001-0001-1',
            'authorIds' => [1],
        ]);

        verify($model->validate())->false();
        verify($model->errors)->arrayHasKey('isbn');
    }

    public function testUpdateWithTheSameAuthorsKeepsTwoRows(): void
    {
        $model = Book::findOne(4);
        $model->authorIds = [1, 2];
        $model->title = 'Двойная звезда, издание второе';

        verify($model->save())->true();
        verify($this->authorRowCount(4))->equals(2);
    }

    public function testUpdateReplacesTheAuthorList(): void
    {
        $model = Book::findOne(4);
        $model->authorIds = [3];

        verify($model->save())->true();
        verify($this->authorRowCount(4))->equals(1);
    }

    private function authorRowCount(int $bookId): int
    {
        return (int) Yii::$app->db
            ->createCommand('SELECT COUNT(*) FROM book_author WHERE book_id = :id', [':id' => $bookId])
            ->queryScalar();
    }
}
