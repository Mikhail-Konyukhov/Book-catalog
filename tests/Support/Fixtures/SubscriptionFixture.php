<?php

declare(strict_types=1);

namespace app\tests\Support\Fixtures;

use app\models\Subscription;
use yii\test\ActiveFixture;

final class SubscriptionFixture extends ActiveFixture
{
    public $modelClass = Subscription::class;
    public $dataFile = '@app/tests/Support/data/subscription.php';
}
