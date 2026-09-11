<?php

declare(strict_types=1);

namespace app\tests\Functional;

use app\models\Subscription;
use app\tests\Support\Fixtures\AuthorFixture;
use app\tests\Support\Fixtures\SubscriptionFixture;
use app\tests\Support\FunctionalTester;
use Codeception\Example;

final class SubscriptionCest
{
    /** Свободный номер: +79991110000 занят фикстурами на авторах 1 и 2. */
    private const PHONE = '+79995550000';

    public function _fixtures(): array
    {
        return [
            'author' => AuthorFixture::class,
            'subscription' => SubscriptionFixture::class,
        ];
    }

    /**
     * D2. Мусор вместо номера — ошибка формы, записи нет.
     *
     * @example ["123", "российским номером"]
     * @example ["abc", "российским номером"]
     * @example ["", "cannot be blank"]
     */
    public function garbagePhoneIsRejected(FunctionalTester $I, Example $example): void
    {
        $I->wantTo('мусорный номер отбивается формой, а не сохраняется');
        $I->amOnRoute('author/view', ['id' => 12]);
        $I->submitForm('.author-view form', ['Subscription[phone]' => $example[0]]);

        $I->seeResponseCodeIs(200);
        $I->see($example[1]);
        $I->dontSee('Вы подписаны');
        $I->assertSame(0, $this->countFor(12));
    }

    /**
     * D5. Несуществующий author_id — редирект на список, не 500.
     */
    public function unknownAuthorRedirectsToIndex(FunctionalTester $I): void
    {
        $I->wantTo('подписка на несуществующего автора уводит на список авторов без 500');
        $I->amOnRoute('author/view', ['id' => 12]);
        $I->submitForm('.author-view form', [
            'Subscription[author_id]' => 9999,
            'Subscription[phone]' => self::PHONE,
        ]);

        $I->seeResponseCodeIs(200);
        $I->seeInCurrentUrl('r=author%2Findex');
        $I->dontSeeRecord(Subscription::class, ['phone' => self::PHONE]);
        $I->assertSame(0, $this->countFor(9999));
    }

    /**
     * D7. Подписка гостем.
     */
    public function guestSubscribes(FunctionalTester $I): void
    {
        $I->wantTo('гость подписывается на автора и видит подтверждение');
        $I->amOnRoute('author/view', ['id' => 12]);
        $I->submitForm('.author-view form', ['Subscription[phone]' => self::PHONE]);

        $I->seeResponseCodeIs(200);
        $I->seeInCurrentUrl('r=author%2Fview');
        $I->see('Вы подписаны');
        $I->seeRecord(Subscription::class, ['author_id' => 12, 'phone' => self::PHONE]);
    }

    /**
     * D8. Один номер на обоих соавторов книги 7 — две строки.
     */
    public function sameNumberOnBothCoauthors(FunctionalTester $I): void
    {
        $I->wantTo('один номер подписывается на обоих соавторов и даёт две строки');
        $this->subscribe($I, 3);
        $this->subscribe($I, 4);

        $I->seeRecord(Subscription::class, ['author_id' => 3, 'phone' => self::PHONE]);
        $I->seeRecord(Subscription::class, ['author_id' => 4, 'phone' => self::PHONE]);
        $I->assertSame(2, (int) Subscription::find()->where(['phone' => self::PHONE])->count());

        // Ни одна из них ещё не уведомлена: смс уходит при выходе книги, не при подписке.
        $I->assertSame(
            2,
            (int) Subscription::find()->where(['phone' => self::PHONE, 'notified_at' => null])->count()
        );
    }

    private function subscribe(FunctionalTester $I, int $authorId): void
    {
        $I->amOnRoute('author/view', ['id' => $authorId]);
        $I->submitForm('.author-view form', ['Subscription[phone]' => self::PHONE]);
        $I->see('Вы подписаны');
    }

    private function countFor(int $authorId): int
    {
        return (int) Subscription::find()->where(['author_id' => $authorId])->count();
    }
}
