<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Author $model */
/** @var app\models\Subscription $subscription */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\widgets\DetailView;

$this->title = $model->fullName;
$this->params['breadcrumbs'][] = ['label' => 'Авторы', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="author-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php if (!Yii::$app->user->isGuest) : ?>
        <p>
            <?= Html::a('Редактировать', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Удалить', ['delete', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data' => ['method' => 'post', 'confirm' => 'Удалить автора?'],
            ]) ?>
        </p>
    <?php endif ?>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => ['id', 'last_name', 'first_name', 'middle_name'],
    ]) ?>

    <h2>Книги</h2>

    <?php if ($model->books) : ?>
        <ul>
            <?php foreach ($model->books as $book) : ?>
                <li><?= Html::a(Html::encode($book->title), ['/book/view', 'id' => $book->id]) ?> (<?= (int) $book->year ?>)</li>
            <?php endforeach ?>
        </ul>
    <?php else : ?>
        <p>Книг пока нет.</p>
    <?php endif ?>

    <h2>Подписка на новые книги</h2>

    <?php $form = ActiveForm::begin(['action' => ['/subscription/create']]) ?>
        <?= Html::activeHiddenInput($subscription, 'author_id') ?>
        <?= $form->field($subscription, 'phone')->textInput(['placeholder' => '+79991234567']) ?>
        <?= Html::submitButton('Подписаться', ['class' => 'btn btn-primary']) ?>
    <?php ActiveForm::end() ?>

</div>
