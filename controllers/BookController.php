<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\Book;
use app\models\BookSearch;
use app\models\Subscription;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

class BookController extends Controller
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
        $searchModel = new BookSearch();

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $searchModel->search($this->request->queryParams),
        ]);
    }

    public function actionView(int $id): string
    {
        return $this->render('view', ['model' => $this->findModel($id)]);
    }

    public function actionCreate(): Response|string
    {
        $model = new Book();

        if ($model->load($this->request->post())) {
            $model->coverFile = UploadedFile::getInstance($model, 'coverFile');

            if ($model->save()) {
                // Уведомление вне save(): у Book объявлены transactions(),
                // и запрос к внешнему сервису держал бы транзакцию открытой.
                Subscription::notifyNewBook($model);

                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate(int $id): Response|string
    {
        $model = $this->findModel($id);

        if ($model->load($this->request->post())) {
            $model->coverFile = UploadedFile::getInstance($model, 'coverFile');

            if ($model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('update', ['model' => $model]);
    }

    public function actionDelete(int $id): Response
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    private function findModel(int $id): Book
    {
        $model = Book::findOne($id);

        if ($model === null) {
            throw new NotFoundHttpException('Книга не найдена.');
        }

        return $model;
    }
}
