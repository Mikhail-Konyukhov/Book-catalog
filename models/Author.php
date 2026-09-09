<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Query;

/**
 * @property int $id
 * @property string $last_name
 * @property string $first_name
 * @property string|null $middle_name
 * @property-read string $fullName
 * @property-read Book[] $books
 */
class Author extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%author}}';
    }

    public function rules(): array
    {
        return [
            [['last_name', 'first_name'], 'required'],
            [['last_name', 'first_name', 'middle_name'], 'string', 'max' => 100],
            ['middle_name', 'default', 'value' => null],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'last_name' => 'Фамилия',
            'first_name' => 'Имя',
            'middle_name' => 'Отчество',
            'fullName' => 'ФИО',
        ];
    }

    public function getFullName(): string
    {
        return implode(' ', array_filter([$this->last_name, $this->first_name, $this->middle_name]));
    }

    public function getBooks(): ActiveQuery
    {
        return $this->hasMany(Book::class, ['id' => 'book_id'])
            ->viaTable('{{%book_author}}', ['author_id' => 'id']);
    }

    public function getSubscriptions(): ActiveQuery
    {
        return $this->hasMany(Subscription::class, ['author_id' => 'id']);
    }

    /**
     * ТОП-10 авторов по числу книг, выпущенных в указанном году. Один запрос.
     *
     * @return array<int, array{id: int, last_name: string, first_name: string,
     *     middle_name: string|null, books_count: int}>
     */
    public static function topByYear(int $year): array
    {
        $rows = (new Query())
            ->select([
                'id' => 'a.id',
                'last_name' => 'a.last_name',
                'first_name' => 'a.first_name',
                'middle_name' => 'a.middle_name',
                'books_count' => 'COUNT(*)',
            ])
            ->from(['ba' => '{{%book_author}}'])
            ->innerJoin(['b' => '{{%book}}'], 'b.id = ba.book_id')
            ->innerJoin(['a' => '{{%author}}'], 'a.id = ba.author_id')
            ->where(['b.year' => $year])
            // Группировка по первичному ключу: при ONLY_FULL_GROUP_BY остальные
            // поля автора функционально зависимы только от него.
            ->groupBy('a.id')
            ->orderBy(['books_count' => SORT_DESC, 'a.id' => SORT_ASC])
            ->limit(10)
            ->all();

        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['books_count'] = (int) $row['books_count'];
        }

        return $rows;
    }
}
