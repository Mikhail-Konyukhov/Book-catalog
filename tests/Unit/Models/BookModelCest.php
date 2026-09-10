<?php

declare(strict_types=1);

namespace app\tests\Unit\Models;

use app\models\Book;
use app\tests\Support\Fixtures\AuthorFixture;
use app\tests\Support\Fixtures\BookAuthorFixture;
use app\tests\Support\Fixtures\BookFixture;
use app\tests\Support\UnitTester;
use Yii;

final class BookModelCest
{
    public function _fixtures(): array
    {
        return [
            'author' => AuthorFixture::class,
            'book' => BookFixture::class,
            'bookAuthor' => BookAuthorFixture::class,
        ];
    }

    public function yearAboveTheUpperBound(UnitTester $I): void
    {
        $I->wantTo('год 99999 отбивается формой, а не пятисоткой из MySQL');
        // 99999 не влезает в SMALLINT UNSIGNED: без верхней границы это 500, а не ошибка формы.
        $model = new Book([
            'title' => 'Из будущего',
            'year' => 99999,
            'isbn' => '978-5-0002-0001-0',
            'authorIds' => [1],
        ]);

        verify($model->validate())->false();
        verify($model->errors)->arrayHasKey('year');
    }

    public function unknownAuthorId(UnitTester $I): void
    {
        $I->wantTo('подделанный POST с несуществующим автором не доходит до внешнего ключа');
        $model = new Book([
            'title' => 'Ничей автор',
            'year' => 2021,
            'isbn' => '978-5-0002-0002-7',
            'authorIds' => [999],
        ]);

        verify($model->validate())->false();
        verify($model->errors)->arrayHasKey('authorIds');
    }

    public function duplicateIsbn(UnitTester $I): void
    {
        $I->wantTo('повторный ISBN не проходит валидацию');
        $model = new Book([
            'title' => 'Двойник',
            'year' => 2021,
            'isbn' => '978-5-0001-0001-1',
            'authorIds' => [1],
        ]);

        verify($model->validate())->false();
        verify($model->errors)->arrayHasKey('isbn');
    }

    public function updateWithTheSameAuthors(UnitTester $I): void
    {
        $I->wantTo('правка книги тем же составом авторов не задваивает связи');
        $model = Book::findOne(4);
        $model->authorIds = [1, 2];
        $model->title = 'Двойная звезда, издание второе';

        verify($model->save())->true();
        verify($this->authorRowCount(4))->equals(2);
    }

    public function updateReplacesTheAuthorList(UnitTester $I): void
    {
        $I->wantTo('правка книги новым составом авторов заменяет прежний, а не дописывает');
        $model = Book::findOne(4);
        $model->authorIds = [3];

        verify($model->save())->true();
        verify($this->authorRowCount(4))->equals(1);
    }

    public function duplicateAuthorIdsAreCollapsed(UnitTester $I): void
    {
        $I->wantTo('подделанный POST с одним автором дважды сохраняется, а не падает дублем PK');
        $model = new Book([
            'title' => 'Дубль в POST',
            'year' => 2021,
            'isbn' => '978-5-0002-0003-4',
            'authorIds' => [1, '1'],
        ]);

        verify($model->save())->true();
        verify($this->authorRowCount((int) $model->id))->equals(1);
    }

    private function authorRowCount(int $bookId): int
    {
        return (int) Yii::$app->db
            ->createCommand('SELECT COUNT(*) FROM book_author WHERE book_id = :id', [':id' => $bookId])
            ->queryScalar();
    }
}
