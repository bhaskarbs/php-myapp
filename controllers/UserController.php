<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\UserRecord;
use Yii;
use yii\filters\ContentNegotiator;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;
use yii\web\UploadedFile;

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
                    'delete' => ['DELETE'],
                    'upload-image' => ['POST'],
                ],
            ],
        ];
    }

    public function actionUploadImage(int $id): array
    {
        $model = UserRecord::findOne($id);

        if ($model === null) {
            Yii::$app->response->statusCode = 404;

            return ['success' => false, 'error' => 'User not found'];
        }

        $image = UploadedFile::getInstanceByName('image');

        if ($image === null) {
            Yii::$app->response->statusCode = 400;

            return ['success' => false, 'error' => 'No image uploaded'];
        }

        // basic validations
        $allowed = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($image->type, $allowed, true)) {
            Yii::$app->response->statusCode = 400;

            return ['success' => false, 'error' => 'Unsupported image type'];
        }

        if ($image->size > 2 * 1024 * 1024) { // 2MB limit
            Yii::$app->response->statusCode = 400;

            return ['success' => false, 'error' => 'Image too large'];
        }

        $uploadDir = Yii::getAlias('@webroot') . '/uploads/users/' . $model->id;

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            Yii::$app->response->statusCode = 500;

            return ['success' => false, 'error' => 'Failed to create upload directory'];
        }

        $filename = uniqid('img_', true) . '.' . pathinfo($image->name, PATHINFO_EXTENSION);
        $filePath = $uploadDir . '/' . $filename;

        if ($image->saveAs($filePath)) {
            $url = Yii::getAlias('@web') . '/uploads/users/' . $model->id . '/' . $filename;

            // optionally save to user record, e.g. $model->avatar = $url; $model->save(false);

            return ['success' => true, 'url' => $url];
        }

        Yii::$app->response->statusCode = 500;

        return ['success' => false, 'error' => 'Failed to save image'];
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

    public function actionDelete(int $id): array
    {
        $model = UserRecord::findOne($id);

        if ($model === null) {
            Yii::$app->response->statusCode = 404;

            return [
                'success' => false,
                'error' => 'User not found',
            ];
        }

        if ($model->delete() !== false) {
            Yii::$app->response->statusCode = 200;

            return [
                'success' => true,
                'message' => 'User deleted',
            ];
        }

        Yii::$app->response->statusCode = 422;

        return [
            'success' => false,
            'errors' => $model->errors ?? [],
        ];
    }
}
