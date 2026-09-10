<?php

declare(strict_types=1);

namespace app\tests\Functional;

use app\tests\Support\Fixtures\AuthorFixture;
use app\tests\Support\Fixtures\BookAuthorFixture;
use app\tests\Support\Fixtures\BookFixture;
use app\tests\Support\FunctionalTester;

final class ReportCest
{
    public function _fixtures(): array
    {
        return [
            'author' => AuthorFixture::class,
            'book' => BookFixture::class,
            'bookAuthor' => BookAuthorFixture::class,
        ];
    }

    public function guestOpensTheReport(FunctionalTester $I): void
    {
        $I->wantTo('гость открывает отчёт и видит ТОП-10 без одиннадцатого автора');
        $I->amOnRoute('report/index', ['ReportForm[year]' => 2020]);
        $I->seeResponseCodeIs(200);
        $I->see('Абрамов');
        $I->dontSee('Лебедев'); // author 11 is outside the top ten
    }

    public function garbageYearShowsAFormErrorInsteadOfAnException(FunctionalTester $I): void
    {
        $I->wantTo('мусор вместо года даёт ошибку формы, а не исключение');
        $I->amOnRoute('report/index', ['ReportForm[year]' => 'мусор']);
        $I->seeResponseCodeIs(200);
        $I->see('must be an integer');
    }

    public function yearAboveTheUpperBoundShowsAFormError(FunctionalTester $I): void
    {
        $I->wantTo('год выше верхней границы даёт ошибку формы');
        $I->amOnRoute('report/index', ['ReportForm[year]' => 99999]);
        $I->seeResponseCodeIs(200);
        $I->see('must be no greater than');
    }
}
