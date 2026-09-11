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

/**
 * Матрица прав: роли × действия, сценарии A1-A14.
 *
 * A1-A3 закрыты в BookCest и здесь не дублируются.
 *
 * POST без формы шлётся через sendAjaxPostRequest: в вёрстке удаление и выход —
 * ссылки с data-method="post", их BrowserKit не жмёт. Код ответа при этом
 * настоящий, но адрес редиректа уходит в X-Redirect вместо Location,
 * поэтому такие случаи проверяются кодом ответа и состоянием базы.
 */
final class AccessCest
{
    private const MISSING_ID = 999999;

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

    // --- A4 -------------------------------------------------------------

    public function userSeesTheBookUpdateForm(FunctionalTester $I): void
    {
        $I->wantTo('вошедший пользователь открывает форму правки книги');
        $I->amLoggedInAs(100);
        $I->amOnRoute('book/update', ['id' => 1]);
        $I->seeResponseCodeIs(200);
        $I->seeElement('input[name="Book[title]"]');
    }

    // --- A5 -------------------------------------------------------------

    public function guestCannotDeleteABookByPost(FunctionalTester $I): void
    {
        $I->wantTo('гость POST-ом на удаление книги ничего не удаляет');
        $I->sendAjaxPostRequest('/index.php?r=book/delete&id=1');
        $I->seeResponseCodeIs(302);
        $I->seeRecord(Book::class, ['id' => 1]);
    }

    public function userDeletesABookByPost(FunctionalTester $I): void
    {
        $I->wantTo('вошедший пользователь удаляет книгу POST-ом');
        $I->amLoggedInAs(100);
        $I->sendAjaxPostRequest('/index.php?r=book/delete&id=1');
        $I->seeResponseCodeIs(302);
        $I->dontSeeRecord(Book::class, ['id' => 1]);
    }

    // --- A6 -------------------------------------------------------------

    public function guestSeesTheAuthorListAndCard(FunctionalTester $I): void
    {
        $I->wantTo('гость видит список авторов и карточку автора');
        $I->amOnRoute('author/index');
        $I->seeResponseCodeIs(200);
        $I->see('Абрамов');

        $I->amOnRoute('author/view', ['id' => 1]);
        $I->seeResponseCodeIs(200);
        $I->see('Абрамов');
    }

    public function userSeesTheAuthorListAndCard(FunctionalTester $I): void
    {
        $I->wantTo('вошедший пользователь видит список авторов и карточку автора');
        $I->amLoggedInAs(100);
        $I->amOnRoute('author/index');
        $I->seeResponseCodeIs(200);
        $I->see('Абрамов');

        $I->amOnRoute('author/view', ['id' => 1]);
        $I->seeResponseCodeIs(200);
        $I->see('Абрамов');
    }

    // --- A7 -------------------------------------------------------------

    public function guestIsSentToLoginFromTheAuthorForms(FunctionalTester $I): void
    {
        $I->wantTo('гостя с форм автора уводит на вход');
        $I->amOnRoute('author/create');
        $I->see('Login', 'h1');

        $I->amOnRoute('author/update', ['id' => 1]);
        $I->see('Login', 'h1');
    }

    public function guestGetsThreeOhTwoFromTheAuthorCreateForm(FunctionalTester $I): void
    {
        $I->wantTo('гостю форма создания автора отвечает именно редиректом');
        $I->stopFollowingRedirects();
        $I->amOnRoute('author/create');
        $I->seeResponseCodeIs(302);
    }

    public function userSeesTheAuthorForms(FunctionalTester $I): void
    {
        $I->wantTo('вошедший пользователь видит формы создания и правки автора');
        $I->amLoggedInAs(100);
        $I->amOnRoute('author/create');
        $I->seeResponseCodeIs(200);
        $I->seeElement('input[name="Author[last_name]"]');

        $I->amOnRoute('author/update', ['id' => 1]);
        $I->seeResponseCodeIs(200);
        $I->seeElement('input[name="Author[first_name]"]');
    }

    // --- A8 -------------------------------------------------------------

    public function guestCannotDeleteAnAuthorByPost(FunctionalTester $I): void
    {
        $I->wantTo('гость POST-ом на удаление автора ничего не удаляет');
        $I->sendAjaxPostRequest('/index.php?r=author/delete&id=13');
        $I->seeResponseCodeIs(302);
        $I->seeRecord(Author::class, ['id' => 13]);
    }

    public function userDeletesAnAuthorByPost(FunctionalTester $I): void
    {
        $I->wantTo('вошедший пользователь удаляет автора POST-ом');
        $I->amLoggedInAs(100);
        $I->sendAjaxPostRequest('/index.php?r=author/delete&id=13');
        $I->seeResponseCodeIs(302);
        $I->dontSeeRecord(Author::class, ['id' => 13]);
    }

    // --- A9 -------------------------------------------------------------

    public function guestSubscribesToAnAuthor(FunctionalTester $I): void
    {
        $I->wantTo('гость подписывается на автора со страницы автора');
        $I->amOnRoute('author/view', ['id' => 4]);
        $I->submitForm('.author-view form', ['Subscription[phone]' => '+79995550000']);
        $I->seeRecord(Subscription::class, ['author_id' => 4, 'phone' => '+79995550000']);
    }

    public function userSubscribesToAnAuthor(FunctionalTester $I): void
    {
        $I->wantTo('вошедший пользователь подписывается на автора');
        $I->amLoggedInAs(100);
        $I->amOnRoute('author/view', ['id' => 4]);
        $I->submitForm('.author-view form', ['Subscription[phone]' => '+79995551111']);
        $I->seeRecord(Subscription::class, ['author_id' => 4, 'phone' => '+79995551111']);
    }

    // --- A10 ------------------------------------------------------------

    public function bothRolesOpenTheReport(FunctionalTester $I): void
    {
        $I->wantTo('отчёт открыт и гостю, и вошедшему пользователю');
        $I->amOnRoute('report/index', ['ReportForm[year]' => 2020]);
        $I->seeResponseCodeIs(200);

        $I->amLoggedInAs(100);
        $I->amOnRoute('report/index', ['ReportForm[year]' => 2020]);
        $I->seeResponseCodeIs(200);
    }

    // --- A11 ------------------------------------------------------------

    public function getOnDeleteRoutesIsRejected(FunctionalTester $I): void
    {
        $I->wantTo('GET на удаление отвечает 405 и ничего не удаляет');
        $I->amLoggedInAs(100);

        $I->amOnRoute('book/delete', ['id' => 1]);
        $I->seeResponseCodeIs(405);
        $I->seeRecord(Book::class, ['id' => 1]);

        $I->amOnRoute('author/delete', ['id' => 13]);
        $I->seeResponseCodeIs(405);
        $I->seeRecord(Author::class, ['id' => 13]);
    }

    public function getOnSubscriptionCreateIsRejected(FunctionalTester $I): void
    {
        $I->wantTo('GET на создание подписки отвечает 405');
        $I->amOnRoute('subscription/create', [
            'Subscription[author_id]' => 4,
            'Subscription[phone]' => '+79995552222',
        ]);
        $I->seeResponseCodeIs(405);
        $I->dontSeeRecord(Subscription::class, ['phone' => '+79995552222']);
    }

    // --- A12 ------------------------------------------------------------

    public function guestPostingPastTheFormCreatesNoBook(FunctionalTester $I): void
    {
        $I->wantTo('гость с валидным телом мимо формы книгу не заводит');
        $I->sendAjaxPostRequest('/index.php?r=book/create', [
            'Book[title]' => 'Книга мимо формы',
            'Book[year]' => 2020,
            'Book[isbn]' => '978-5-0002-0001-1',
            'Book[description]' => 'Проверка того, что права висят на действии.',
            'Book[authorIds]' => [1],
        ]);
        $I->seeResponseCodeIs(302);
        $I->dontSeeRecord(Book::class, ['isbn' => '978-5-0002-0001-1']);
    }

    // --- A13 ------------------------------------------------------------

    public function missingIdGivesFourOhFour(FunctionalTester $I): void
    {
        $I->wantTo('несуществующий id даёт 404, а не 500');
        $I->amOnRoute('book/view', ['id' => self::MISSING_ID]);
        $I->seeResponseCodeIs(404);

        $I->amOnRoute('author/view', ['id' => self::MISSING_ID]);
        $I->seeResponseCodeIs(404);

        $I->amLoggedInAs(100);
        $I->amOnRoute('book/update', ['id' => self::MISSING_ID]);
        $I->seeResponseCodeIs(404);
    }

    // --- A14 ------------------------------------------------------------

    public function afterLogoutTheCreateFormIsClosedAgain(FunctionalTester $I): void
    {
        $I->wantTo('после выхода форма создания книги снова недоступна');
        $I->amOnRoute('site/login');
        $I->submitForm('#login-form', [
            'LoginForm[username]' => 'admin',
            'LoginForm[password]' => 'admin',
        ]);
        $I->see('Выход (admin)');

        $I->amOnRoute('book/create');
        $I->seeResponseCodeIs(200);

        $I->sendAjaxPostRequest('/index.php?r=site/logout');
        $I->seeResponseCodeIs(302);

        $I->amOnRoute('book/create');
        $I->see('Login', 'h1');
    }
}
