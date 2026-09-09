<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\ReportForm $model */
/** @var array<int, array{id: int, last_name: string, first_name: string, middle_name: string|null, books_count: int}> $rows */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

$this->title = 'ТОП-10 авторов за год';
?>
<div class="report-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php $form = ActiveForm::begin(['method' => 'get', 'action' => ['index']]) ?>
        <?= $form->field($model, 'year')->textInput(['type' => 'number']) ?>
        <?= Html::submitButton('Показать', ['class' => 'btn btn-primary']) ?>
    <?php ActiveForm::end() ?>

    <?php if ($rows): ?>
        <table class="table table-striped mt-4">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Автор</th>
                    <th>Книг за год</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $i => $row): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= Html::encode(implode(' ', array_filter([
                            $row['last_name'],
                            $row['first_name'],
                            $row['middle_name'],
                        ]))) ?></td>
                        <td><?= $row['books_count'] ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    <?php elseif ($model->year !== null && !$model->hasErrors()): ?>
        <p class="mt-4">За <?= Html::encode((string) $model->year) ?> год книг нет.</p>
    <?php endif ?>

</div>
