<?php

declare(strict_types=1);

namespace app\tests\Unit\Models;

use app\models\Author;
use app\tests\Support\Fixtures\AuthorFixture;
use app\tests\Support\Fixtures\BookAuthorFixture;
use app\tests\Support\Fixtures\BookFixture;

final class AuthorTest extends \Codeception\Test\Unit
{
    public function _fixtures(): array
    {
        return [
            'author' => AuthorFixture::class,
            'book' => BookFixture::class,
            'bookAuthor' => BookAuthorFixture::class,
        ];
    }

    public function testFullNameJoinsThreeParts(): void
    {
        verify(Author::findOne(1)->getFullName())->equals('Абрамов Андрей Петрович');
    }

    public function testFullNameWithoutMiddleNameHasNoTrailingSpace(): void
    {
        verify(Author::findOne(12)->getFullName())->equals('Морозов Никита');
    }

    public function testTopByYearIsOrderedByBookCountAndLimitedToTen(): void
    {
        $top = Author::topByYear(2020);

        verify($top)->arrayCount(10);
        verify(array_column($top, 'id'))->equals([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);
        verify(array_column($top, 'books_count'))->equals([4, 3, 2, 2, 1, 1, 1, 1, 1, 1]);
        verify($top[0]['last_name'])->equals('Абрамов');
    }

    public function testTopByYearCountsOnlyTheRequestedYear(): void
    {
        // Author 12 has one book in 2019 and one in 2020, so neither year gives him two.
        $top = Author::topByYear(2019);

        verify($top)->arrayCount(1);
        verify($top[0]['id'])->equals(12);
        verify($top[0]['books_count'])->equals(1);
    }

    public function testTopByYearForAYearWithoutBooksIsEmpty(): void
    {
        verify(Author::topByYear(2005))->equals([]);
    }
}
