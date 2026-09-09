<?php

declare(strict_types=1);

namespace app\models;

use app\components\SmsSender;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Query;

/**
 * @property int $id
 * @property int $author_id
 * @property string $phone
 * @property int $created_at
 * @property int|null $notified_at
 * @property-read Author $author
 */
class Subscription extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%subscription}}';
    }

    public function behaviors(): array
    {
        return [
            ['class' => TimestampBehavior::class, 'updatedAtAttribute' => false],
        ];
    }

    public function rules(): array
    {
        return [
            [['author_id', 'phone'], 'required'],
            ['author_id', 'exist', 'targetClass' => Author::class, 'targetAttribute' => 'id'],
            ['phone', 'match', 'pattern' => '/^\+7\d{10}$/', 'message' => 'Телефон должен быть российским номером, например +79991234567.'],
            [
                'phone',
                'unique',
                'targetAttribute' => ['author_id', 'phone'],
                'message' => 'На этого автора вы уже подписаны.',
            ],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'author_id' => 'Автор',
            'phone' => 'Телефон',
        ];
    }

    public function beforeValidate(): bool
    {
        if (!parent::beforeValidate()) {
            return false;
        }

        // Нормализация до валидации: иначе unique сравнивает сырую строку и «+7 999…»
        // с «8999…» проходят как разные, а уникальный индекс отдаёт 500 вместо ошибки формы.
        $this->phone = self::normalizePhone((string) $this->phone);

        return true;
    }

    public function getAuthor(): ActiveQuery
    {
        return $this->hasOne(Author::class, ['id' => 'author_id']);
    }

    /**
     * Приводит номер к виду +7XXXXXXXXXX. То, что к нему не сводится,
     * возвращается как есть — отбраковкой занимается валидатор.
     */
    public static function normalizePhone(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if (strlen($digits) === 10) {
            $digits = '7' . $digits;
        } elseif (strlen($digits) === 11 && $digits[0] === '8') {
            $digits[0] = '7';
        }

        return strlen($digits) === 11 && $digits[0] === '7' ? '+' . $digits : $raw;
    }

    /**
     * Уведомляет подписчиков авторов книги. Возвращает число адресатов.
     * Ошибка отправки не роняет создание книги: notified_at просто
     * не проставляется, и по колонке видно, кому смс не ушла.
     */
    public static function notifyNewBook(Book $book): int
    {
        $condition = [
            'author_id' => (new Query())
                ->select('author_id')
                ->from('{{%book_author}}')
                ->where(['book_id' => $book->id]),
        ];

        $phones = static::find()->select('phone')->distinct()->where($condition)->column();

        if ($phones === []) {
            return 0;
        }

        $text = sprintf('Новая книга «%s» (%d) — подписка на автора.', $book->title, (int) $book->year);

        /** @var SmsSender $sender */
        $sender = Yii::$app->get('smsSender');

        if ($sender->send($phones, $text)) {
            static::updateAll(['notified_at' => time()], $condition);
        }

        Yii::info(
            sprintf('Книга «%s» (id %d): адресатов — %d', $book->title, (int) $book->id, count($phones)),
            __METHOD__
        );

        return count($phones);
    }
}
