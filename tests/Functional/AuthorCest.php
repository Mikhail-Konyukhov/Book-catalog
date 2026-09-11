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
use Yii;
use yii\db\Query;

final class AuthorCest
{
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

    /**
     * C3. Пустые обязательные поля формы автора.
     */
    public function emptyRequiredFieldsAreRejected(FunctionalTester $I): void
    {
        $I->wantTo('пустые фамилия и имя дают ошибки формы, а не сохранение');
        $I->amLoggedInAs(100);
        $I->amOnRoute('author/create');
        $I->submitForm('.author-form form', [
            'Author[last_name]' => '',
            'Author[first_name]' => '',
            'Author[middle_name]' => 'Ничейкин',
        ]);

        $I->seeResponseCodeIs(200);
        $I->see('cannot be blank');
        $I->dontSeeRecord(Author::class, ['middle_name' => 'Ничейкин']);
    }

    /**
     * C4. Юзер создаёт автора.
     */
    public function userCreatesAuthor(FunctionalTester $I): void
    {
        $I->wantTo('вошедший пользователь создаёт автора и попадает на его карточку');
        $I->amLoggedInAs(100);
        $I->amOnRoute('author/create');
        $I->submitForm('.author-form form', [
            'Author[last_name]' => 'Тестов',
            'Author[first_name]' => 'Тест',
            'Author[middle_name]' => 'Тестович',
        ]);

        $I->seeResponseCodeIs(200);
        $I->seeInCurrentUrl('r=author%2Fview');
        $I->see('Тестов Тест Тестович', 'h1');
        $I->seeRecord(Author::class, ['last_name' => 'Тестов', 'first_name' => 'Тест']);

        $I->amOnRoute('author/index');
        $I->see('Тестов');
    }

    /**
     * C5. Каскад по книгам: связи исчезли, книги живы.
     */
    public function deletingAuthorKeepsBooks(FunctionalTester $I): void
    {
        $I->wantTo('удаление автора уносит связи book_author, но не сами книги');
        $I->assertSame(2, $this->linksOfAuthor(3), 'у автора 3 должно быть две книги до удаления');

        $I->amLoggedInAs(100);
        $this->deleteAuthor($I, 3);

        $I->dontSeeRecord(Author::class, ['id' => 3]);
        $I->assertSame(0, $this->linksOfAuthor(3));
        $I->seeRecord(Book::class, ['id' => 6]);
        $I->seeRecord(Book::class, ['id' => 7]);

        // Карточка книги открывается и показывает оставшегося автора.
        $I->amOnRoute('book/view', ['id' => 6]);
        $I->seeResponseCodeIs(200);
        $I->see('Белов');
        $I->dontSee('Веселов');
    }

    /**
     * C5, второй случай. Книга, оставшаяся без единственного автора.
     */
    public function bookWithoutAuthorsStillOpens(FunctionalTester $I): void
    {
        $I->wantTo('книга без единственного автора не роняет карточку');
        // Книги 2 и 3 держатся на авторе 1 и больше ни на ком.
        $I->assertSame(1, $this->authorsOfBook(2));

        $I->amLoggedInAs(100);
        $this->deleteAuthor($I, 1);

        $I->assertSame(0, $this->authorsOfBook(2));
        $I->seeRecord(Book::class, ['id' => 2]);

        $I->amOnRoute('book/view', ['id' => 2]);
        $I->seeResponseCodeIs(200);
        $I->see('Тень над городом', 'h1');

        $I->amOnRoute('book/index');
        $I->seeResponseCodeIs(200);
    }

    /**
     * C6. Каскад по подпискам.
     */
    public function deletingAuthorRemovesSubscriptions(FunctionalTester $I): void
    {
        $I->wantTo('удаление автора уносит его подписки, а список авторов открывается');
        $I->seeRecord(Subscription::class, ['id' => 4, 'author_id' => 13]);

        $I->amLoggedInAs(100);
        $this->deleteAuthor($I, 13);

        $I->dontSeeRecord(Subscription::class, ['id' => 4]);
        $I->dontSeeRecord(Author::class, ['id' => 13]);
        // Чужие подписки на месте.
        $I->seeRecord(Subscription::class, ['id' => 1]);

        $I->amOnRoute('author/index');
        $I->seeResponseCodeIs(200);
        $I->dontSee('Нилов');
    }

    /**
     * Удаление идёт POST-ом: в разметке это ссылка с data-method, а JS
     * в функциональном сьюте не работает.
     */
    private function deleteAuthor(FunctionalTester $I, int $id): void
    {
        $I->sendAjaxPostRequest(Yii::$app->urlManager->createUrl(['author/delete', 'id' => $id]));
    }

    private function linksOfAuthor(int $authorId): int
    {
        return (int) (new Query())->from('{{%book_author}}')->where(['author_id' => $authorId])->count();
    }

    private function authorsOfBook(int $bookId): int
    {
        return (int) (new Query())->from('{{%book_author}}')->where(['book_id' => $bookId])->count();
    }
}
