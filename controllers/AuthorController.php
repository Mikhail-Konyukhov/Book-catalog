<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\Author;
use app\models\Subscription;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class AuthorController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['actions' => ['index', 'view'], 'allow' => true, 'roles' => ['?', '@']],
                    ['actions' => ['create', 'update', 'delete'], 'allow' => true, 'roles' => ['@']],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['delete' => ['post']],
            ],
        ];
    }

    public function actionIndex(): string
    {
        return $this->render('index', [
            'dataProvider' => new ActiveDataProvider([
                'query' => Author::find(),
                'sort' => ['defaultOrder' => ['last_name' => SORT_ASC]],
            ]),
        ]);
    }

    public function actionView(int $id): string
    {
        $model = $this->findModel($id);

        return $this->render('view', [
            'model' => $model,
            // Форма подписки живёт на странице автора, поэтому пустую модель даёт этот экшен.
            'subscription' => new Subscription(['author_id' => $model->id]),
        ]);
    }

    public function actionCreate(): Response|string
    {
        $model = new Author();

        if ($model->load($this->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate(int $id): Response|string
    {
        $model = $this->findModel($id);

        if ($model->load($this->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', ['model' => $model]);
    }

    public function actionDelete(int $id): Response
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    private function findModel(int $id): Author
    {
        $model = Author::findOne($id);

        if ($model === null) {
            throw new NotFoundHttpException('Автор не найден.');
        }

        return $model;
    }
}
