<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\Subscription;
use Yii;
use yii\db\IntegrityException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

class SubscriptionController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['actions' => ['create'], 'allow' => true, 'roles' => ['?', '@']],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['create' => ['post']],
            ],
        ];
    }

    public function actionCreate(): Response
    {
        $model = new Subscription();
        $model->load($this->request->post());

        try {
            $saved = $model->save();
        } catch (IntegrityException) {
            // Гонка двух POST доходит до уникального индекса раньше валидатора.
            // Подписка всё равно есть — это успех.
            $saved = true;
        }

        Yii::$app->session->setFlash(
            $saved ? 'success' : 'error',
            $saved ? 'Вы подписаны на нового автора.' : implode(' ', $model->getFirstErrors())
        );

        // Ошибка на author_id означает, что такой страницы автора нет, — там же и 404.
        return $model->hasErrors('author_id')
            ? $this->redirect(['/author/index'])
            : $this->redirect(['/author/view', 'id' => $model->author_id]);
    }
}
