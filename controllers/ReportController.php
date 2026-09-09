<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\Author;
use app\models\ReportForm;
use yii\web\Controller;

class ReportController extends Controller
{
    // AccessControl здесь не ставится сознательно: отчёт открыт всем,
    // включая неаутентифицированного гостя (ответ 4 работодателя).
    public function actionIndex(): string
    {
        $model = new ReportForm();
        $rows = $model->load($this->request->queryParams) && $model->validate()
            ? Author::topByYear((int) $model->year)
            : [];

        return $this->render('index', ['model' => $model, 'rows' => $rows]);
    }
}
