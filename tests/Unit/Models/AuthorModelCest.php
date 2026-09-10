<?php

declare(strict_types=1);

namespace app\tests\Unit\Models;

use app\models\Author;
use app\tests\Support\Fixtures\AuthorFixture;
use app\tests\Support\Fixtures\BookAuthorFixture;
use app\tests\Support\Fixtures\BookFixture;
use app\tests\Support\UnitTester;

final class AuthorModelCest
{
    public function _fixtures(): array
    {
        return [
            'author' => AuthorFixture::class,
            'book' => BookFixture::class,
            'bookAuthor' => BookAuthorFixture::class,
        ];
    }

    public function fullName(UnitTester $I): void
    {
        $I->wantTo('ФИО автора склеивается из фамилии, имени и отчества');
        verify(Author::findOne(1)->getFullName())->equals('Абрамов Андрей Петрович');
    }

    public function fullNameWithoutMiddleName(UnitTester $I): void
    {
        $I->wantTo('автор без отчества: в ФИО нет висящего пробела');
        verify(Author::findOne(12)->getFullName())->equals('Морозов Никита');
    }

    public function topIsOrderedAndLimited(UnitTester $I): void
    {
        $I->wantTo('отчёт: ровно 10 строк, по убыванию числа книг за год');
        $top = Author::topByYear(2020);

        verify($top)->arrayCount(10);
        verify(array_column($top, 'id'))->equals([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);
        verify(array_column($top, 'books_count'))->equals([4, 3, 2, 2, 1, 1, 1, 1, 1, 1]);
        verify($top[0]['last_name'])->equals('Абрамов');
    }

    public function topCountsOnlyTheRequestedYear(UnitTester $I): void
    {
        $I->wantTo('отчёт считает книги только запрошенного года, а не все подряд');
        // У автора 12 одна книга в 2019 и одна в 2020 - ни один год не даёт ему двух.
        $top = Author::topByYear(2019);

        verify($top)->arrayCount(1);
        verify($top[0]['id'])->equals(12);
        verify($top[0]['books_count'])->equals(1);
    }

    public function topForAnEmptyYear(UnitTester $I): void
    {
        $I->wantTo('год, в котором книг нет, даёт пустой отчёт, а не ошибку');
        verify(Author::topByYear(2005))->equals([]);
    }
}
