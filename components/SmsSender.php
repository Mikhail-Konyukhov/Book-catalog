<?php

declare(strict_types=1);

namespace app\components;

use Yii;
use yii\base\BaseObject;

/**
 * Отправка смс через smspilot.ru. Один POST на всех адресатов сразу:
 * api принимает список номеров через запятую, и разбивать его на запрос
 * на номер незачем.
 */
class SmsSender extends BaseObject
{
    /** Пустой ключ означает «не отправляем»; факт пишется в лог, а не проглатывается. */
    public string $apiKey = '';

    public string $endpoint = 'https://smspilot.ru/api.php';

    /** Без таймаута зависший smspilot подвешивает страницу создания книги. */
    public int $timeout = 5;

    public function send(array $phones, string $text): bool
    {
        if ($phones === []) {
            return false;
        }

        if ($this->apiKey === '') {
            Yii::warning('SMSPILOT_KEY пуст — смс не отправлены, адресатов: ' . count($phones), __METHOD__);

            return false;
        }

        $curl = curl_init($this->endpoint);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'send' => $text,
                'to' => implode(',', $phones),
                'apikey' => $this->apiKey,
                'format' => 'json',
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
        ]);

        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($body === false || $status !== 200) {
            Yii::error(sprintf('smspilot недоступен: HTTP %d, %s', $status, $error), __METHOD__);

            return false;
        }

        $answer = json_decode((string) $body, true);

        // Ошибку smspilot отдаёт двумястами и полем error, а не кодом ответа.
        if (!is_array($answer) || isset($answer['error'])) {
            Yii::error('smspilot отказал: ' . (string) $body, __METHOD__);

            return false;
        }

        Yii::info(sprintf('Отправлено смс: %d', count($phones)), __METHOD__);

        return true;
    }
}
