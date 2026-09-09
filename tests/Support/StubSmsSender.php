<?php

declare(strict_types=1);

namespace app\tests\Support;

use app\components\SmsSender;

/**
 * Отправщик-заглушка: запоминает номера и отдаёт заданный результат,
 * чтобы проверять notified_at без обращения к smspilot.
 */
final class StubSmsSender extends SmsSender
{
    /** @var string[] */
    public array $phones = [];

    public bool $result = false;

    public function send(array $phones, string $text): bool
    {
        $this->phones = $phones;

        return $this->result;
    }
}
