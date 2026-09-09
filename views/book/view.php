<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Book $model */

use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Книги', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="book-view">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php if (!Yii::$app->user->isGuest) : ?>
        <p>
            <?= Html::a('Редактировать', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Удалить', ['delete', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data' => ['method' => 'post', 'confirm' => 'Удалить книгу?'],
            ]) ?>
        </p>
    <?php endif ?>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'title',
            'year',
            'isbn',
            'description:ntext',
            [
                'label' => 'Авторы',
                'format' => 'html',
                'value' => implode(', ', array_map(
                    static fn ($a): string => Html::a(Html::encode($a->fullName), ['author/view', 'id' => $a->id]),
                    $model->authors
                )),
            ],
            [
                'label' => 'Обложка',
                'format' => 'html',
                'value' => $model->cover_path === null
                    ? null
                    : Html::img($model->coverUrl, ['alt' => '', 'height' => 200]),
            ],
            'created_at:datetime',
        ],
    ]) ?>
</div>
