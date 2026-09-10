<?php

declare(strict_types=1);

namespace app\tests\Unit\Models;

use app\components\SmsSender;
use app\models\Book;
use app\models\Subscription;
use app\tests\Support\Fixtures\AuthorFixture;
use app\tests\Support\Fixtures\BookAuthorFixture;
use app\tests\Support\Fixtures\BookFixture;
use app\tests\Support\Fixtures\SubscriptionFixture;
use app\tests\Support\StubSmsSender;
use app\tests\Support\UnitTester;
use Codeception\Example;
use Yii;

final class SubscriptionModelCest
{
    public function _fixtures(): array
    {
        return [
            'author' => AuthorFixture::class,
            'book' => BookFixture::class,
            'bookAuthor' => BookAuthorFixture::class,
            'subscription' => SubscriptionFixture::class,
        ];
    }

    /**
     * @example ["8 999 123-45-67"]
     * @example ["+79991234567"]
     * @example ["9991234567"]
     * @example ["+7 (999) 123 45 67"]
     * @example ["79991234567"]
     */
    public function normalizePhone(UnitTester $I, Example $example): void
    {
        $I->wantTo('номер приводится к +79991234567 из формата');
        verify(Subscription::normalizePhone($example[0]))->equals('+79991234567');
    }

    public function sameNumberInAnotherFormatIsRejected(UnitTester $I): void
    {
        $I->wantTo('повторная подписка тем же номером в другом формате отбивается формой');
        $model = new Subscription(['author_id' => 1, 'phone' => '8 999 111-00-00']);

        verify($model->validate())->false();
        verify($model->errors)->arrayHasKey('phone');
    }

    public function sameNumberForAnotherAuthorIsAllowed(UnitTester $I): void
    {
        $I->wantTo('тот же номер можно подписать на другого автора');
        $model = new Subscription(['author_id' => 4, 'phone' => '8 999 111-00-00']);

        verify($model->validate())->true();
        verify($model->phone)->equals('+79991110000');
    }

    public function oneNumberIsCountedOnce(UnitTester $I): void
    {
        $I->wantTo('номер, подписанный на обоих авторов книги, получает одну смс, а не две');
        verify(Subscription::notifyNewBook(Book::findOne(4)))->equals(1);
    }

    public function bookWithoutSubscribers(UnitTester $I): void
    {
        $I->wantTo('книга без подписчиков не даёт адресатов');
        verify(Subscription::notifyNewBook(Book::findOne(12)))->equals(0);
    }

    public function notifiedAtMarksOnlyTheAddressees(UnitTester $I): void
    {
        $I->wantTo('колонка notified_at проставляется подпискам на авторов книги и не трогает чужие');
        $sender = $this->stubSender(true);

        verify(Subscription::notifyNewBook(Book::findOne(4)))->equals(1);
        verify($sender->phones)->equals(['+79991110000']);
        // Подписки 1 и 2 - на авторов книги 4, подписка 3 - на постороннего автора.
        verify(Subscription::findOne(1)->notified_at)->notNull();
        verify(Subscription::findOne(2)->notified_at)->notNull();
        verify(Subscription::findOne(3)->notified_at)->null();
    }

    public function failedSendLeavesNotifiedAtEmpty(UnitTester $I): void
    {
        $I->wantTo('отказ smspilot оставляет notified_at пустым, а книгу - сохранённой');
        $this->stubSender(false);

        verify(Subscription::notifyNewBook(Book::findOne(4)))->equals(1);
        verify(Subscription::findOne(1)->notified_at)->null();
    }

    public function emptyKeyDoesNotSend(UnitTester $I): void
    {
        $I->wantTo('пустой SMSPILOT_KEY означает отказ, а не молчаливый успех');
        verify((new SmsSender())->send(['+79991110000'], 'text'))->false();
    }

    private function stubSender(bool $result): StubSmsSender
    {
        $sender = new StubSmsSender();
        $sender->result = $result;
        Yii::$app->set('smsSender', $sender);

        return $sender;
    }
}
