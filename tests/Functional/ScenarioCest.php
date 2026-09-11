<?php

declare(strict_types=1);

namespace app\tests\Functional;

use app\models\Author;
use app\models\Book;
use app\models\Subscription;
use app\tests\Support\Fixtures\AuthorFixture;
use app\tests\Support\Fixtures\BookAuthorFixture;
use app\tests\Support\Fixtures\BookFixture;
use app\tests\Support\Fixtures\SubscriptionFixture;
use app\tests\Support\Fixtures\UserFixture;
use app\tests\Support\FunctionalTester;
use app\tests\Support\StubSmsSender;
use Yii;
use yii\db\Query;
use yii\helpers\Url;

/**
 * Сквозные цепочки, сценарии G1-G8.
 *
 * Каждый метод - последовательность действий целиком, а не одна проверка:
 * поодиночке эти куски зелёные, ломаются стыки между ними.
 * Отправка смс подменяется StubSmsSender - живой smspilot в сюиту не тащим.
 */
final class ScenarioCest
{
    private const YEAR = 2021;

    public function _fixtures(): array
    {
        return [
            'user' => UserFixture::class,
            'author' => AuthorFixture::class,
            'book' => BookFixture::class,
            'bookAuthor' => BookAuthorFixture::class,
            'subscription' => SubscriptionFixture::class,
        ];
    }

    public function theWholeAssignmentEndToEnd(FunctionalTester $I): void
    {
        $I->wantTo('пройти основной путь ТЗ целиком: автор, подписка, книга, смс, отчёт');
        $sender = $this->stubSender();
        $phone = '+79995550001';

        $I->amLoggedInAs(100);
        $author = $this->createAuthor($I, 'Новиков');
        $this->logout($I);

        $this->subscribe($I, $author->id, $phone);
        $I->seeRecord(Subscription::class, ['author_id' => $author->id, 'phone' => $phone]);

        $I->amLoggedInAs(100);
        $book = $this->createBook($I, 'Первая книга', '978-5-0002-0001-1', [$author->id]);

        // Смс ушла ровно на подписанный номер, и подписка помечена как уведомлённая.
        $I->assertSame([$phone], $sender->phones);
        $I->assertNotNull($this->notifiedAt($I, ['phone' => $phone]));

        $this->logout($I);
        $I->amOnRoute('book/index');
        $I->seeResponseCodeIs(200);
        $I->see('Первая книга');

        $I->amOnRoute('report/index', ['ReportForm[year]' => self::YEAR]);
        $I->seeResponseCodeIs(200);
        $I->see('Новиков', 'tbody tr:nth-child(1) td:nth-child(2)');
        $I->see('1', 'tbody tr:nth-child(1) td:nth-child(3)');
        $I->assertSame(self::YEAR, (int) $book->year);
    }

    public function aSubscriptionMadeAfterTheBookGetsNoSms(FunctionalTester $I): void
    {
        $I->wantTo('подписка после выхода книги не рассылает смс задним числом');
        $sender = $this->stubSender();

        $I->amLoggedInAs(100);
        $author = $this->createAuthor($I, 'Орлов');
        $this->createBook($I, 'Вышла раньше', '978-5-0002-0002-2', [$author->id]);
        $I->assertSame([], $sender->phones);

        $this->logout($I);
        $this->subscribe($I, $author->id, '+79995550002');

        $I->assertSame([], $sender->phones);
        $I->assertNull($this->notifiedAt($I, ['phone' => '+79995550002']));
    }

    public function oneNumberOnBothCoauthorsGetsOneSms(FunctionalTester $I): void
    {
        $I->wantTo('один номер, подписанный на обоих соавторов, получает одну смс');
        $sender = $this->stubSender();
        $phone = '+79995550003';

        $I->amLoggedInAs(100);
        $first = $this->createAuthor($I, 'Панов');
        $second = $this->createAuthor($I, 'Рогов');
        $this->logout($I);

        $this->subscribe($I, $first->id, $phone);
        $this->subscribe($I, $second->id, $phone);

        $I->amLoggedInAs(100);
        $this->createBook($I, 'Соавторство', '978-5-0002-0003-3', [$first->id, $second->id]);

        $I->assertSame([$phone], $sender->phones);
        $I->assertNotNull($this->notifiedAt($I, ['author_id' => $first->id]));
        $I->assertNotNull($this->notifiedAt($I, ['author_id' => $second->id]));
    }

    public function changingAuthorsDoesNotNotify(FunctionalTester $I): void
    {
        $I->wantTo('правка состава авторов книги не рассылает уведомлений');
        $sender = $this->stubSender();
        $phone = '+79995550004';

        $I->amLoggedInAs(100);
        $written = $this->createAuthor($I, 'Сомов');
        $subscribed = $this->createAuthor($I, 'Тихонов');
        $book = $this->createBook($I, 'Смена авторов', '978-5-0002-0004-4', [$written->id]);

        $this->logout($I);
        $this->subscribe($I, $subscribed->id, $phone);

        $I->amLoggedInAs(100);
        $I->amOnRoute('book/update', ['id' => $book->id]);
        $I->submitForm('form', ['Book[authorIds]' => [(string) $subscribed->id]]);

        // По ТЗ смс приходит о поступлении книги, а не о правке карточки.
        // Фиксируем как ожидаемое поведение, чтобы позже не считалось багом.
        $I->assertSame([], $sender->phones);
        $I->assertNull($this->notifiedAt($I, ['phone' => $phone]));
        $I->assertSame([$subscribed->id], $this->authorIdsOf($book->id));
    }

    public function deletingAnAuthorKeepsTheBooks(FunctionalTester $I): void
    {
        $I->wantTo('удаление автора не уносит его книги и не ломает отчёт');
        $this->stubSender();

        $I->amLoggedInAs(100);
        $author = $this->createAuthor($I, 'Уваров');
        $book = $this->createBook($I, 'Осталась одна', '978-5-0002-0005-5', [$author->id]);
        $this->logout($I);
        $this->subscribe($I, $author->id, '+79995550005');

        $I->amLoggedInAs(100);
        $I->sendAjaxPostRequest(Url::to(['/author/delete', 'id' => $author->id]));

        $I->dontSeeRecord(Author::class, ['id' => $author->id]);
        $I->dontSeeRecord(Subscription::class, ['author_id' => $author->id]);
        $I->seeRecord(Book::class, ['id' => $book->id]);
        $I->assertSame([], $this->authorIdsOf($book->id));

        // Книга без единственного автора и отчёт за её год не падают.
        $I->amOnRoute('book/view', ['id' => $book->id]);
        $I->seeResponseCodeIs(200);
        $I->amOnRoute('report/index', ['ReportForm[year]' => self::YEAR]);
        $I->seeResponseCodeIs(200);
    }

    public function deletingABookKeepsTheAuthors(FunctionalTester $I): void
    {
        $I->wantTo('удаление книги уносит только связи, авторы остаются');
        $this->stubSender();

        $I->amLoggedInAs(100);
        $first = $this->createAuthor($I, 'Фомин');
        $second = $this->createAuthor($I, 'Хомяков');
        $book = $this->createBook($I, 'Под снос', '978-5-0002-0006-6', [$first->id, $second->id]);

        $I->sendAjaxPostRequest(Url::to(['/book/delete', 'id' => $book->id]));

        $I->dontSeeRecord(Book::class, ['id' => $book->id]);
        $I->assertSame([], $this->authorIdsOf($book->id));
        $I->seeRecord(Author::class, ['id' => $first->id]);
        $I->seeRecord(Author::class, ['id' => $second->id]);

        $I->amOnRoute('book/view', ['id' => $book->id]);
        $I->seeResponseCodeIs(404);
        $I->amOnRoute('book/index');
        $I->seeResponseCodeIs(200);
    }

    public function aDuplicateIsbnKeepsTheFilledFields(FunctionalTester $I): void
    {
        $I->wantTo('повтор isbn даёт ошибку формы, не теряет введённое и не пишет вторую запись');
        $this->stubSender();
        $isbn = '978-5-0002-0007-7';

        $I->amLoggedInAs(100);
        $author = $this->createAuthor($I, 'Цветков');
        $this->createBook($I, 'Оригинал', $isbn, [$author->id]);

        $I->amOnRoute('book/create');
        $I->submitForm('form', [
            'Book[title]' => 'Дубль',
            'Book[year]' => (string) self::YEAR,
            'Book[isbn]' => $isbn,
            'Book[authorIds]' => [(string) $author->id],
        ]);

        $I->seeResponseCodeIs(200);
        $I->see('has already been taken');
        $I->seeInField('Book[title]', 'Дубль');
        $I->seeInField('Book[isbn]', $isbn);
        $I->dontSeeRecord(Book::class, ['title' => 'Дубль']);
        $I->assertSame(1, (int) Book::find()->where(['isbn' => $isbn])->count());
    }

    public function guestThenUserThenGuest(FunctionalTester $I): void
    {
        $I->wantTo('пройти гость - юзер - гость в одной сессии');
        $this->stubSender();
        $phone = '+79995550008';

        $this->subscribe($I, 1, $phone);
        $I->seeRecord(Subscription::class, ['phone' => $phone]);

        $I->amOnRoute('book/create');
        $I->see('Login', 'h1');

        $I->amLoggedInAs(100);
        $author = $this->createAuthor($I, 'Чернов');
        $this->createBook($I, 'Между входом и выходом', '978-5-0002-0008-8', [$author->id]);

        $this->logout($I);
        $I->amOnRoute('book/create');
        $I->see('Login', 'h1');
        $I->seeRecord(Subscription::class, ['phone' => $phone]);
    }

    /**
     * Подмена компонента переживает запрос: recreateApplication по умолчанию false,
     * приложение между запросами одного теста не пересоздаётся.
     */
    private function stubSender(bool $result = true): StubSmsSender
    {
        $sender = new StubSmsSender();
        $sender->result = $result;
        Yii::$app->set('smsSender', $sender);

        return $sender;
    }

    private function logout(FunctionalTester $I): void
    {
        $I->sendAjaxPostRequest(Url::to(['/site/logout']));
    }

    private function createAuthor(FunctionalTester $I, string $lastName): Author
    {
        $I->amOnRoute('author/create');
        $I->submitForm('form', [
            'Author[last_name]' => $lastName,
            'Author[first_name]' => 'Пётр',
            'Author[middle_name]' => 'Петрович',
        ]);

        $author = $I->grabRecord(Author::class, ['last_name' => $lastName]);
        assert($author instanceof Author);

        return $author;
    }

    /**
     * @param int[] $authorIds
     */
    private function createBook(FunctionalTester $I, string $title, string $isbn, array $authorIds): Book
    {
        $I->amOnRoute('book/create');
        $I->submitForm('form', [
            'Book[title]' => $title,
            'Book[year]' => (string) self::YEAR,
            'Book[isbn]' => $isbn,
            'Book[authorIds]' => array_map('strval', $authorIds),
        ]);

        $book = $I->grabRecord(Book::class, ['isbn' => $isbn]);
        assert($book instanceof Book);

        return $book;
    }

    /**
     * @param array<string, mixed> $condition
     */
    private function notifiedAt(FunctionalTester $I, array $condition): ?int
    {
        $subscription = $I->grabRecord(Subscription::class, $condition);
        assert($subscription instanceof Subscription);

        return $subscription->notified_at;
    }

    private function subscribe(FunctionalTester $I, int $authorId, string $phone): void
    {
        $I->amOnRoute('author/view', ['id' => $authorId]);
        $I->submitForm('form', ['Subscription[phone]' => $phone]);
    }

    /**
     * @return int[] id авторов книги, прямо из таблицы связи
     */
    private function authorIdsOf(int $bookId): array
    {
        return array_map('intval', (new Query())
            ->select('author_id')
            ->from('{{%book_author}}')
            ->where(['book_id' => $bookId])
            ->orderBy(['author_id' => SORT_ASC])
            ->column());
    }
}
