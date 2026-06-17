<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\UserRecord;
use Yii;
use yii\filters\ContentNegotiator;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

class UserController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors(): array
    {
        return [
            'contentNegotiator' => [
                'class' => ContentNegotiator::class,
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'create' => ['POST'],
                ],
            ],
        ];
    }

    public function actionCreate(): array
    {
        $model = new UserRecord();
        $model->load(Yii::$app->request->bodyParams, '');

        if ($model->save()) {
            Yii::$app->response->statusCode = 201;

            return [
                'success' => true,
                'data' => [
                    'id' => $model->id,
                    'name' => $model->name,
                    'email' => $model->email,
                ],
            ];
        }

        Yii::$app->response->statusCode = 422;

        return [
            'success' => false,
            'errors' => $model->errors,
        ];
    }
}
