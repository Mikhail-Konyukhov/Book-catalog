<?php

declare(strict_types=1);

namespace app\tests\Unit\Models;

use app\models\Book;
use app\tests\Support\Fixtures\AuthorFixture;
use app\tests\Support\Fixtures\BookAuthorFixture;
use app\tests\Support\Fixtures\BookFixture;
use app\tests\Support\UnitTester;
use Yii;
use yii\web\UploadedFile;

final class BookModelCest
{
    /** @var string[] подделки загруженных файлов, убираются после теста */
    private array $_tempFiles = [];

    public function _after(UnitTester $I): void
    {
        foreach ($this->_tempFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        $this->_tempFiles = [];
    }

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

    public function emptyModelReportsEveryRequiredField(UnitTester $I): void
    {
        $I->wantTo('пустая форма книги называет все обязательные поля разом');
        $model = new Book();

        verify($model->validate())->false();
        verify($model->errors)->arrayHasKey('title');
        verify($model->errors)->arrayHasKey('year');
        verify($model->errors)->arrayHasKey('isbn');
        verify($model->errors)->arrayHasKey('authorIds');
    }

    public function titleLongerThan255IsRejected(UnitTester $I): void
    {
        $I->wantTo('название длиннее колонки отбивается формой, а не обрезается базой');
        $model = new Book([
            'title' => str_repeat('а', 256),
            'year' => 2021,
            'isbn' => '978-5-0002-0004-1',
            'authorIds' => [1],
        ]);

        verify($model->validate())->false();
        verify($model->errors)->arrayHasKey('title');
    }

    public function yearBelowTheLowerBound(UnitTester $I): void
    {
        $I->wantTo('год до 1450 не проходит нижнюю границу');
        $model = new Book([
            'title' => 'До Гутенберга',
            'year' => 1449,
            'isbn' => '978-5-0002-0005-8',
            'authorIds' => [1],
        ]);

        verify($model->validate())->false();
        verify($model->errors)->arrayHasKey('year');
    }

    public function singleAuthorGivesOneLinkRow(UnitTester $I): void
    {
        $I->wantTo('книга с одним автором даёт ровно одну строку связи');
        $model = new Book([
            'title' => 'Одиночка',
            'year' => 2021,
            'isbn' => '978-5-0002-0006-5',
            'authorIds' => [1],
        ]);

        verify($model->save())->true();
        verify($this->authorRowCount((int) $model->id))->equals(1);
    }

    public function emptyAuthorListIsRejected(UnitTester $I): void
    {
        $I->wantTo('книга без единого автора не сохраняется');
        $model = new Book([
            'title' => 'Без авторов',
            'year' => 2021,
            'isbn' => '978-5-0002-0007-2',
            'authorIds' => [],
        ]);

        verify($model->validate())->false();
        verify($model->errors)->arrayHasKey('authorIds');
    }

    public function ownIsbnOnUpdateIsNotADuplicate(UnitTester $I): void
    {
        $I->wantTo('сохранение книги со своим же isbn не ловится проверкой уникальности');
        $model = Book::findOne(1);

        verify($model->validate())->true();
        verify($model->save())->true();
    }

    public function bookSavesWithoutACover(UnitTester $I): void
    {
        $I->wantTo('книга сохраняется без обложки, поле остаётся пустым');
        $model = new Book([
            'title' => 'Без картинки',
            'year' => 2021,
            'isbn' => '978-5-0002-0008-9',
            'authorIds' => [1],
        ]);

        verify($model->save())->true();
        verify($model->cover_path)->null();
    }

    public function pngCoverLandsInUploads(UnitTester $I): void
    {
        $I->wantTo('png в пределах лимита ложится в web/uploads, и cover_path ведёт к файлу');
        $model = new Book([
            'title' => 'С обложкой',
            'year' => 2021,
            'isbn' => '978-5-0002-0009-6',
            'authorIds' => [1],
            'coverFile' => $this->upload('cover.png'),
        ]);

        verify($model->save())->true();
        verify($model->cover_path)->stringContainsString(Book::COVER_DIR . '/');
        verify($model->cover_path)->stringEndsWith('.png');

        $file = Yii::getAlias('@app/web/' . $model->cover_path);
        verify(is_file($file))->true();
        unlink($file);
    }

    public function phpFileIsRejected(UnitTester $I): void
    {
        $I->wantTo('файл с расширением php не проходит валидацию и не попадает в uploads');
        $before = $this->uploadsCount();
        $model = new Book([
            'title' => 'Не картинка',
            'year' => 2021,
            'isbn' => '978-5-0002-0010-2',
            'authorIds' => [1],
            'coverFile' => $this->upload('shell.php'),
        ]);

        verify($model->save())->false();
        verify($model->errors)->arrayHasKey('coverFile');
        verify($this->uploadsCount())->equals($before);
    }

    public function oversizedCoverIsRejected(UnitTester $I): void
    {
        $I->wantTo('обложка больше двух мегабайт даёт ошибку формы, а не пятисотку');
        $model = new Book([
            'title' => 'Тяжёлая обложка',
            'year' => 2021,
            'isbn' => '978-5-0002-0011-9',
            'authorIds' => [1],
            'coverFile' => $this->upload('cover.png', 3 * 1024 * 1024),
        ]);

        verify($model->save())->false();
        verify($model->errors)->arrayHasKey('coverFile');
    }

    /**
     * Подделка загруженного файла без HTTP: saveAs() копирует через поток,
     * если объекту передан tempResource, — move_uploaded_file в CLI вернул бы false.
     * Размер подменяется числом: файл на два мегабайта ради одной проверки не нужен.
     */
    private function upload(string $name, ?int $size = null): UploadedFile
    {
        $path = Yii::getAlias('@runtime/' . uniqid('upload', true) . '-' . $name);
        $this->_tempFiles[] = $path;
        // Однопиксельный png: mimeTypes читает не имя файла, а его содержимое.
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        );
        file_put_contents($path, str_ends_with($name, '.php') ? '<?php echo 1;' : $png);

        return new UploadedFile([
            'name' => $name,
            'tempName' => $path,
            'type' => str_ends_with($name, '.php') ? 'application/x-php' : 'image/png',
            'size' => $size ?? filesize($path),
            'error' => UPLOAD_ERR_OK,
            'tempResource' => fopen($path, 'rb'),
        ]);
    }

    private function uploadsCount(): int
    {
        $dir = Yii::getAlias('@app/web/' . Book::COVER_DIR);

        return is_dir($dir) ? count(scandir($dir)) : 0;
    }

    private function authorRowCount(int $bookId): int
    {
        return (int) Yii::$app->db
            ->createCommand('SELECT COUNT(*) FROM book_author WHERE book_id = :id', [':id' => $bookId])
            ->queryScalar();
    }
}
