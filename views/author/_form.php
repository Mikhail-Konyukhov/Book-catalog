<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Author $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
?>
<div class="author-form">
    <?php $form = ActiveForm::begin() ?>

    <?= $form->field($model, 'last_name')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'first_name')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'middle_name')->textInput(['maxlength' => true]) ?>

    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end() ?>
</div>
