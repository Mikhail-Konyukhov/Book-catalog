<?php

declare(strict_types=1);

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * Поиск по книгам. Наследует Book, чтобы правила и подписи не разъезжались.
 */
class BookSearch extends Book
{
    public function rules(): array
    {
        return [
            [['title', 'isbn'], 'safe'],
            ['year', 'integer'],
        ];
    }

    public function scenarios(): array
    {
        return Model::scenarios();
    }

    public function search(array $params): ActiveDataProvider
    {
        // with('authors') — иначе список книг делает запрос на каждую строку.
        $query = Book::find()->with('authors');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere(['year' => $this->year])
            ->andFilterWhere(['like', 'title', $this->title])
            ->andFilterWhere(['like', 'isbn', $this->isbn]);

        return $dataProvider;
    }
}
