<?php

declare(strict_types=1);

namespace app\tests\Unit\Models;

use app\models\Book;
use app\models\Subscription;
use app\tests\Support\Fixtures\AuthorFixture;
use app\tests\Support\Fixtures\BookAuthorFixture;
use app\tests\Support\Fixtures\BookFixture;
use app\tests\Support\Fixtures\SubscriptionFixture;

final class SubscriptionTest extends \Codeception\Test\Unit
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
     * @dataProvider phoneProvider
     */
    public function testNormalizePhoneAcceptsEveryCommonFormat(string $raw): void
    {
        verify(Subscription::normalizePhone($raw))->equals('+79991234567');
    }

    public static function phoneProvider(): array
    {
        return [
            ['8 999 123-45-67'],
            ['+79991234567'],
            ['9991234567'],
            ['+7 (999) 123 45 67'],
            ['79991234567'],
        ];
    }

    public function testSecondSubscriptionWithTheSamePhoneInAnotherFormatIsInvalid(): void
    {
        $model = new Subscription(['author_id' => 1, 'phone' => '8 999 111-00-00']);

        verify($model->validate())->false();
        verify($model->errors)->arrayHasKey('phone');
    }

    public function testSubscriptionToAnotherAuthorWithTheSamePhoneIsValid(): void
    {
        $model = new Subscription(['author_id' => 4, 'phone' => '8 999 111-00-00']);

        verify($model->validate())->true();
        verify($model->phone)->equals('+79991110000');
    }

    public function testNotifyNewBookCountsOnePhoneOnce(): void
    {
        // Book 4 has authors 1 and 2, and the same phone is subscribed to both.
        verify(Subscription::notifyNewBook(Book::findOne(4)))->equals(1);
    }

    public function testNotifyNewBookWithoutSubscribersReturnsZero(): void
    {
        verify(Subscription::notifyNewBook(Book::findOne(12)))->equals(0);
    }
}
