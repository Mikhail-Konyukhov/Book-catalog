<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Book $model */

use app\models\Author;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\helpers\ArrayHelper;

// ponytail: список авторов собирается здесь, а не приходит параметром, —
// иначе одна и та же строка дублировалась бы в create и update.
$authors = ArrayHelper::map(Author::find()->orderBy('last_name')->all(), 'id', 'fullName');
?>
<div class="book-form">
    <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]) ?>

    <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'year')->textInput(['type' => 'number']) ?>
    <?= $form->field($model, 'isbn')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'description')->textarea(['rows' => 5]) ?>
    <?= $form->field($model, 'authorIds')->dropDownList($authors, ['multiple' => true, 'size' => 8]) ?>
    <?= $form->field($model, 'coverFile')->fileInput() ?>

    <?php if ($model->cover_path !== null) : ?>
        <p><?= Html::img($model->coverUrl, ['alt' => '', 'height' => 120]) ?></p>
    <?php endif ?>

    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end() ?>
</div>
