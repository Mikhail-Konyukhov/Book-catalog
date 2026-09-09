<?php

declare(strict_types=1);

namespace app\tests\Functional;

use app\tests\Support\Fixtures\AuthorFixture;
use app\tests\Support\Fixtures\BookAuthorFixture;
use app\tests\Support\Fixtures\BookFixture;
use app\tests\Support\Fixtures\UserFixture;
use app\tests\Support\FunctionalTester;

final class BookCest
{
    public function _fixtures(): array
    {
        return [
            'user' => UserFixture::class,
            'author' => AuthorFixture::class,
            'book' => BookFixture::class,
            'bookAuthor' => BookAuthorFixture::class,
        ];
    }

    public function guestSeesTheBookList(FunctionalTester $I): void
    {
        $I->amOnRoute('book/index');
        $I->seeResponseCodeIs(200);
        $I->see('Река времени');
    }

    public function guestSeesABookCard(FunctionalTester $I): void
    {
        $I->amOnRoute('book/view', ['id' => 1]);
        $I->seeResponseCodeIs(200);
        $I->see('Река времени');
        $I->see('Абрамов');
    }

    public function guestIsSentToLoginFromTheCreateForm(FunctionalTester $I): void
    {
        $I->amOnRoute('book/create');
        $I->see('Login', 'h1');
    }

    public function guestIsSentToLoginFromTheUpdateForm(FunctionalTester $I): void
    {
        $I->amOnRoute('book/update', ['id' => 1]);
        $I->see('Login', 'h1');
    }

    public function userSeesTheCreateForm(FunctionalTester $I): void
    {
        $I->amLoggedInAs(100);
        $I->amOnRoute('book/create');
        $I->seeResponseCodeIs(200);
        $I->seeElement('input[name="Book[title]"]');
        $I->seeElement('select[name="Book[authorIds][]"]');
    }
}
