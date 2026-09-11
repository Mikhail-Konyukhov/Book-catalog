<?php

declare(strict_types=1);

namespace app\tests\Functional;

use app\models\Book;
use app\tests\Support\Fixtures\AuthorFixture;
use app\tests\Support\Fixtures\BookAuthorFixture;
use app\tests\Support\Fixtures\BookFixture;
use app\tests\Support\Fixtures\UserFixture;
use app\tests\Support\FunctionalTester;
use Yii;

/**
 * Форма книги и список: сценарии B17, B18, B20.
 * Права и вид формы уже проверены в BookCest — здесь не дублируются.
 */
final class BookFormCest
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

    public function updateWithoutAFileKeepsTheCover(FunctionalTester $I): void
    {
        $I->wantTo('правка книги без новой обложки не затирает прежнюю');
        $I->amLoggedInAs(100);
        $I->amOnRoute('book/update', ['id' => 1]);
        $I->submitForm('form', [
            'Book[title]' => 'Река времени, издание второе',
            'Book[year]' => 2020,
            'Book[isbn]' => '978-5-0001-0001-1',
            'Book[description]' => 'Роман о времени.',
            'Book[authorIds]' => [1],
        ]);

        $I->seeRecord(Book::class, [
            'id' => 1,
            'title' => 'Река времени, издание второе',
            'cover_path' => 'uploads/cover1.jpg',
        ]);
    }

    public function titleFilterNarrowsTheList(FunctionalTester $I): void
    {
        $I->wantTo('фильтр по названию сужает список книг');
        $I->amOnRoute('book/index', ['BookSearch[title]' => 'Мост']);
        $I->seeResponseCodeIs(200);
        $I->see('Мост');
        $I->dontSee('Река времени');
    }

    public function yearFilterNarrowsTheList(FunctionalTester $I): void
    {
        $I->wantTo('фильтр по году оставляет только книги этого года');
        $I->amOnRoute('book/index', ['BookSearch[year]' => 2019]);
        $I->seeResponseCodeIs(200);
        $I->see('Прошлогодний снег');
        $I->dontSee('Река времени');
    }

    public function emptyListSaysSoInsteadOfBlankPage(FunctionalTester $I): void
    {
        $I->wantTo('пустой каталог отдаёт 200 и явное «ничего не найдено», а не белый экран');
        // Чистится в самом тесте: фикстуры вернут строки перед следующим.
        Yii::$app->db->createCommand()->delete('{{%book_author}}')->execute();
        Yii::$app->db->createCommand()->delete('{{%book}}')->execute();

        $I->amOnRoute('book/index');
        $I->seeResponseCodeIs(200);
        $I->seeElement('.empty');
        // Язык тестового конфига — en-US, отсюда английский текст пустой выдачи.
        $I->see('No results found.');
    }
}
