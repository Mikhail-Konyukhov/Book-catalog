<?php

declare(strict_types=1);

namespace app\tests\Functional;

use app\tests\Support\Fixtures\AuthorFixture;
use app\tests\Support\Fixtures\BookAuthorFixture;
use app\tests\Support\Fixtures\BookFixture;
use app\tests\Support\FunctionalTester;

/**
 * Страница отчёта ТОП-10, сценарии E3-E5.
 *
 * Форма и границы года уже проверены в ReportCest (E6, E7) - здесь только
 * то, что видно в таблице: пустой год, вклад соавторов и порядок строк.
 */
final class ReportPageCest
{
    public function _fixtures(): array
    {
        return [
            'author' => AuthorFixture::class,
            'book' => BookFixture::class,
            'bookAuthor' => BookAuthorFixture::class,
        ];
    }

    public function emptyYearShowsAnExplicitMessage(FunctionalTester $I): void
    {
        $I->wantTo('год без книг даёт 200 и явное «нет данных», а не пустую таблицу');
        $I->amOnRoute('report/index', ['ReportForm[year]' => 2005]);

        $I->seeResponseCodeIs(200);
        $I->see('За 2005 год книг нет.');
        $I->dontSeeElement('table');
    }

    public function aBookOfTwoAuthorsCountsForEachOfThem(FunctionalTester $I): void
    {
        $I->wantTo('книга двух авторов даёт +1 каждому из них');
        $I->amOnRoute('report/index', ['ReportForm[year]' => 2020]);

        // Книга 4 «Двойная звезда» - общая у авторов 1 и 2. Без неё было бы 3 и 2.
        $I->see('Абрамов Андрей Петрович', 'tbody tr:nth-child(1) td:nth-child(2)');
        $I->see('4', 'tbody tr:nth-child(1) td:nth-child(3)');
        $I->see('Белов Борис Ильич', 'tbody tr:nth-child(2) td:nth-child(2)');
        $I->see('3', 'tbody tr:nth-child(2) td:nth-child(3)');
    }

    public function equalCountsAreOrderedById(FunctionalTester $I): void
    {
        $I->wantTo('при равном числе книг авторы идут по возрастанию id');
        $I->amOnRoute('report/index', ['ReportForm[year]' => 2020]);

        // Авторы 5-10 имеют по одной книге за 2020: порядок между ними
        // задаёт только явная сортировка по id в Author::topByYear().
        $I->assertSame(
            ['Дроздов', 'Ершов', 'Жуков', 'Зайцев', 'Ильин', 'Козлов'],
            array_slice($this->lastNames($I), 4, 6)
        );
    }

    public function theSameOrderOnASecondCall(FunctionalTester $I): void
    {
        $I->wantTo('два вызова подряд дают одинаковый порядок строк');
        $I->amOnRoute('report/index', ['ReportForm[year]' => 2020]);
        $first = $this->lastNames($I);

        $I->amOnRoute('report/index', ['ReportForm[year]' => 2020]);

        $I->assertCount(10, $first);
        $I->assertSame($first, $this->lastNames($I));
    }

    /**
     * Фамилии из второй колонки таблицы, в порядке строк.
     *
     * @return string[]
     */
    private function lastNames(FunctionalTester $I): array
    {
        return array_map(
            static fn (string $fio): string => explode(' ', trim($fio))[0],
            $I->grabMultiple('tbody tr td:nth-child(2)')
        );
    }
}
