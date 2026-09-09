<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use yii\bootstrap5\Html;
use yii\grid\GridView;

$this->title = 'Авторы';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="author-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php if (!Yii::$app->user->isGuest) : ?>
        <p><?= Html::a('Добавить автора', ['create'], ['class' => 'btn btn-success']) ?></p>
    <?php endif ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            'last_name',
            'first_name',
            'middle_name',
            [
                'class' => yii\grid\ActionColumn::class,
                'template' => Yii::$app->user->isGuest ? '{view}' : '{view} {update} {delete}',
                'urlCreator' => fn (string $action, $model) => [$action, 'id' => $model->id],
            ],
        ],
    ]) ?>

</div>
