<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\BookSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

use app\models\Book;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Книги';
$this->params['breadcrumbs'][] = $this->title;

$isGuest = Yii::$app->user->isGuest;
?>
<div class="book-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php if (!$isGuest) : ?>
        <p><?= Html::a('Добавить книгу', ['create'], ['class' => 'btn btn-success']) ?></p>
    <?php endif ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            'title',
            'year',
            'isbn',
            [
                'label' => 'Авторы',
                'format' => 'text',
                // Связь предзагружена в BookSearch::search() через with('authors').
                'value' => static fn (Book $book): string => implode(
                    ', ',
                    array_map(static fn ($a): string => $a->fullName, $book->authors)
                ),
            ],
            [
                'class' => ActionColumn::class,
                'template' => $isGuest ? '{view}' : '{view} {update} {delete}',
                'urlCreator' => static fn (string $action, Book $book): string
                    => Yii::$app->urlManager->createUrl(['book/' . $action, 'id' => $book->id]),
                'buttons' => [
                    'delete' => static fn (string $url): string => Html::a('🗑', $url, [
                        'title' => 'Удалить',
                        'data' => ['method' => 'post', 'confirm' => 'Удалить книгу?'],
                    ]),
                ],
            ],
        ],
    ]) ?>
</div>
