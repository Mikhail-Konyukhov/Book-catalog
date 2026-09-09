<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Book $model */

use yii\helpers\Html;

$this->title = 'Добавить книгу';
$this->params['breadcrumbs'][] = ['label' => 'Книги', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="book-create">
    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', ['model' => $model]) ?>
</div>
